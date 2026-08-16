<?php

namespace Tests\Feature\Push;

use App\Contracts\Push\PushTransport;
use App\Data\Push\PushDeliveryResult;
use App\Data\Push\PushMessage;
use App\Jobs\Push\SendPushNotificationJob;
use App\Models\PushNotification;
use App\Models\PushNotificationDelivery;
use App\Models\PushSubscription;
use App\Services\Push\PushDeliveryRecoveryService;
use App\Services\Push\PushNotificationService;
use App\Services\Push\PushSubscriptionCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PushHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('push-subscriptions:127.0.0.1');
        RateLimiter::clear('push-preferences-read:127.0.0.1');
        RateLimiter::clear('push-preferences-write:127.0.0.1');
        RateLimiter::clear('push-clicks:127.0.0.1');
    }

    public function test_subscription_and_preference_mutations_are_rate_limited(): void
    {
        config()->set('firebase.security.subscription_limit', 2);
        $payload = ['token' => str_repeat('a', 40), 'device_uuid' => fake()->uuid(), 'permission_status' => 'granted'];
        $this->postJson(route('push.subscriptions.store'), $payload)->assertSuccessful();
        $this->postJson(route('push.subscriptions.store'), $payload)->assertSuccessful();
        $this->postJson(route('push.subscriptions.store'), $payload)->assertStatus(429);

        RateLimiter::clear('push-preferences-write:127.0.0.1');
        config()->set('firebase.security.preference_write_limit', 1);
        $preference = ['token' => $payload['token'], 'device_uuid' => $payload['device_uuid'], 'topic_ids' => []];
        $this->putJson(route('push.preferences.update'), $preference)->assertSuccessful();
        $this->putJson(route('push.preferences.update'), $preference)->assertStatus(429);
    }

    public function test_click_limiter_is_lightweight_and_preserves_stored_redirect(): void
    {
        config()->set('firebase.security.click_limit', 2);
        $delivery = $this->delivery(['status' => 'accepted']);
        $url = route('push.click', $delivery->public_id);

        $this->get($url)->assertRedirect('https://example.com/news');
        $this->get($url)->assertRedirect('https://example.com/news');
        $this->get($url.'?redirect=https://evil.example')->assertStatus(429);
        $this->assertSame(2, $delivery->fresh()->click_count);
    }

    public function test_accepted_delivery_duplicate_job_does_not_call_fcm_again(): void
    {
        config()->set('firebase.messaging.sending_enabled', true);
        $delivery = $this->delivery(['status' => 'accepted', 'accepted_at' => now()]);
        $transport = new HardeningCountingTransport;

        (new SendPushNotificationJob($delivery->push_subscription_id, [], $delivery->id))
            ->handle(new PushNotificationService($transport));

        $this->assertSame(0, $transport->calls);
        $this->assertSame('accepted', $delivery->fresh()->status);
    }

    public function test_job_contract_has_bounded_progressive_backoff(): void
    {
        config()->set('firebase.messaging.job_tries', 4);
        config()->set('firebase.messaging.job_timeout', 30);
        config()->set('firebase.messaging.job_backoff', [60, 300, 900]);
        $job = new SendPushNotificationJob(1, []);

        $this->assertSame(4, $job->tries);
        $this->assertSame(30, $job->timeout);
        $this->assertSame([60, 300, 900], $job->backoff());
        $this->assertSame('push', $job->queue);
    }

    public function test_stale_attempt_is_recovered_but_fresh_and_accepted_are_not(): void
    {
        Queue::fake();
        config()->set('firebase.maintenance.stuck_after_minutes', 15);
        $stale = $this->delivery(['status' => 'attempting', 'last_attempted_at' => now()->subMinutes(30)]);
        $fresh = $this->delivery(['status' => 'attempting', 'last_attempted_at' => now()]);
        $accepted = $this->delivery(['status' => 'accepted', 'last_attempted_at' => now()->subHour()]);

        $dry = app(PushDeliveryRecoveryService::class)->recover(true, 10);
        $this->assertSame(1, $dry['candidates']);
        $this->assertSame('attempting', $stale->fresh()->status);

        $result = app(PushDeliveryRecoveryService::class)->recover(false, 10);
        $this->assertSame(1, $result['requeued']);
        $this->assertSame('queued', $stale->fresh()->status);
        $this->assertSame('attempting', $fresh->fresh()->status);
        $this->assertSame('accepted', $accepted->fresh()->status);
        Queue::assertPushed(SendPushNotificationJob::class, 1);
    }

    public function test_pruning_is_dry_runnable_retains_active_and_preserves_analytics(): void
    {
        config()->set('firebase.maintenance.inactive_retention_days', 90);
        $old = PushSubscription::factory()->create(['is_active' => false, 'unsubscribed_at' => now()->subDays(100)]);
        $active = PushSubscription::factory()->create(['is_active' => true, 'last_seen_at' => now()->subYear()]);
        $delivery = $this->delivery([], $old);

        $dry = app(PushSubscriptionCleanupService::class)->prune(true, 10);
        $this->assertSame(1, $dry['candidates']);
        $this->assertNotNull($old->fresh());

        $result = app(PushSubscriptionCleanupService::class)->prune(false, 10);
        $this->assertSame(1, $result['deleted']);
        $this->assertNull($old->fresh());
        $this->assertNotNull($active->fresh());
        $this->assertNotNull($delivery->fresh());
        $this->assertNull($delivery->fresh()->push_subscription_id);
    }

    public function test_global_sending_switch_prevents_transport_without_disabling_registration(): void
    {
        config()->set('firebase.messaging.sending_enabled', false);
        $subscription = PushSubscription::factory()->create();
        $transport = new HardeningCountingTransport;
        $result = (new PushNotificationService($transport))->sendToSubscription($subscription, new PushMessage('Title', 'Body'));

        $this->assertFalse($result->success);
        $this->assertSame('SENDING_DISABLED', $result->errorCode);
        $this->assertSame(0, $transport->calls);
        $this->assertTrue($subscription->fresh()->is_active);
        $this->postJson(route('push.subscriptions.store'), [
            'token' => str_repeat('z', 40),
            'device_uuid' => fake()->uuid(),
            'permission_status' => 'granted',
        ])->assertSuccessful();
    }

    public function test_health_command_never_sends_and_reports_disabled_state_safely(): void
    {
        config()->set('firebase.messaging.sending_enabled', false);
        config()->set('firebase.messaging.project_id', null);
        config()->set('firebase.messaging.service_account_path', null);

        $this->artisan('push:health', ['--json' => true])
            ->expectsOutputToContain('"sending":"disabled"')
            ->assertSuccessful();
    }

    /** @param array<string, mixed> $attributes */
    private function delivery(array $attributes = [], ?PushSubscription $subscription = null): PushNotificationDelivery
    {
        $subscription ??= PushSubscription::factory()->create();
        $notification = PushNotification::factory()->create(['target_url' => 'https://example.com/news']);

        return PushNotificationDelivery::factory()
            ->for($notification, 'notification')
            ->for($subscription, 'subscription')
            ->create([...$attributes, 'subscription_token_hash' => $subscription->token_hash]);
    }
}

class HardeningCountingTransport implements PushTransport
{
    public int $calls = 0;

    public function send(string $token, PushMessage $message): PushDeliveryResult
    {
        $this->calls++;

        return PushDeliveryResult::success('unused');
    }
}
