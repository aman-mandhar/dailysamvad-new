<?php

namespace Tests\Feature\Push;

use App\Contracts\Push\PushTransport;
use App\Data\Push\PushDeliveryResult;
use App\Data\Push\PushMessage;
use App\Exceptions\Push\RetryablePushDeliveryException;
use App\Jobs\Push\SendPushNotificationJob;
use App\Models\Category;
use App\Models\Post;
use App\Models\PushNotification;
use App\Models\PushNotificationDelivery;
use App\Models\PushSubscription;
use App\Models\PushTopic;
use App\Models\User;
use App\Services\Push\ManualPushNotificationService;
use App\Services\Push\PostPublishPushAutomation;
use App\Services\Push\PushAnalyticsService;
use App\Services\Push\PushNotificationService;
use App\Services\Push\PushTopicSyncService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PushAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fanout_creates_one_safe_delivery_and_job_per_unique_subscription(): void
    {
        Queue::fake();
        $subscriptions = PushSubscription::factory()->count(2)->create();
        $notification = PushNotification::factory()->create();
        $service = new PushNotificationService(new AnalyticsFakeTransport(PushDeliveryResult::success('unused')));

        $queued = $service->queueToActiveSubscriptions(new PushMessage('Title', 'Body'), PushSubscription::query(), 500, $notification);
        $again = $service->queueToActiveSubscriptions(new PushMessage('Title', 'Body'), PushSubscription::query(), 500, $notification);

        $this->assertSame(2, $queued);
        $this->assertSame(0, $again);
        $this->assertSame(2, $notification->deliveries()->count());
        Queue::assertPushed(SendPushNotificationJob::class, 2);
        $this->assertEqualsCanonicalizing($subscriptions->pluck('token_hash')->all(), $notification->deliveries()->pluck('subscription_token_hash')->all());
        $this->assertFalse(
            PushNotificationDelivery::query()->get()->contains(fn ($delivery): bool => in_array($delivery->subscription_token_hash, $subscriptions->pluck('token')->all(), true))
        );
    }

    public function test_success_records_fcm_accepted_message_id_and_tracking_url(): void
    {
        $delivery = $this->delivery();
        $transport = new AnalyticsFakeTransport(PushDeliveryResult::success('projects/test/messages/123', 200));

        (new SendPushNotificationJob($delivery->push_subscription_id, [], $delivery->id))->handle(new PushNotificationService($transport));

        $delivery->refresh();
        $this->assertSame('accepted', $delivery->status);
        $this->assertSame(1, $delivery->attempt_count);
        $this->assertSame('projects/test/messages/123', $delivery->fcm_message_id);
        $this->assertNotNull($delivery->accepted_at);
        $this->assertStringContainsString($delivery->public_id, $transport->lastMessage->url);
        $this->assertSame('/push/click/'.$delivery->public_id, parse_url($transport->lastMessage->url, PHP_URL_PATH));
        $this->assertNull(parse_url($transport->lastMessage->url, PHP_URL_QUERY));
    }

    public function test_invalid_token_fails_permanently_and_deactivates_subscription(): void
    {
        $delivery = $this->delivery();
        $result = PushDeliveryResult::failure('UNREGISTERED', 'Token invalid.', 404, tokenInvalid: true);

        (new SendPushNotificationJob($delivery->push_subscription_id, [], $delivery->id))->handle(new PushNotificationService(new AnalyticsFakeTransport($result)));

        $delivery->refresh();
        $this->assertSame('failed', $delivery->status);
        $this->assertSame('invalid_token', $delivery->error_category);
        $this->assertFalse($delivery->retryable);
        $this->assertFalse(PushSubscription::query()->findOrFail($delivery->push_subscription_id)->is_active);
    }

    public function test_retryable_failure_then_success_updates_the_same_delivery(): void
    {
        $delivery = $this->delivery();
        $transport = new AnalyticsSequenceTransport([
            PushDeliveryResult::failure('UNAVAILABLE', 'Temporary.', 503, retryable: true),
            PushDeliveryResult::success('message-after-retry'),
        ]);
        $job = new SendPushNotificationJob($delivery->push_subscription_id, [], $delivery->id);

        try {
            $job->handle(new PushNotificationService($transport));
            $this->fail('Retryable result did not request a retry.');
        } catch (RetryablePushDeliveryException) {
            $this->assertSame('server', $delivery->fresh()->error_category);
            $this->assertTrue($delivery->fresh()->retryable);
            $this->assertTrue($delivery->fresh()->subscription->is_active);
        }

        $job->handle(new PushNotificationService($transport));
        $this->assertSame(1, PushNotificationDelivery::query()->count());
        $this->assertSame(2, $delivery->fresh()->attempt_count);
        $this->assertSame('accepted', $delivery->fresh()->status);
    }

    public function test_authentication_failure_does_not_deactivate_subscription(): void
    {
        $delivery = $this->delivery();
        $result = PushDeliveryResult::failure('UNAUTHENTICATED', 'Authentication failed.', 401);

        (new SendPushNotificationJob($delivery->push_subscription_id, [], $delivery->id))->handle(new PushNotificationService(new AnalyticsFakeTransport($result)));

        $this->assertSame('authentication', $delivery->fresh()->error_category);
        $this->assertTrue($delivery->fresh()->subscription->is_active);
    }

    public function test_opaque_click_records_first_repeat_and_redirects_only_to_stored_target(): void
    {
        $delivery = $this->delivery('https://example.com/stored-target');
        $url = route('push.click', $delivery->public_id);

        $this->get($url.'?redirect=https://evil.example')->assertRedirect('https://example.com/stored-target')->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $first = $delivery->fresh()->first_clicked_at;
        $this->get($url)->assertRedirect('https://example.com/stored-target');

        $delivery->refresh();
        $this->assertSame(2, $delivery->click_count);
        $this->assertTrue($delivery->first_clicked_at->equalTo($first));
        $this->assertNotNull($delivery->last_clicked_at);
        $this->assertContains($this->get('/push/click/'.fake()->uuid())->getStatusCode(), [302, 404]);
    }

    public function test_missing_or_unsafe_stored_target_falls_back_to_home(): void
    {
        foreach ([null, 'javascript:alert(1)'] as $target) {
            $delivery = $this->delivery($target);
            $this->get(route('push.click', $delivery->public_id))->assertRedirect(route('home'));
        }
    }

    public function test_metrics_use_unique_clicks_over_fcm_accepted_and_handle_legacy_zero(): void
    {
        $notification = PushNotification::factory()->create(['recipient_count' => 100]);
        PushNotificationDelivery::factory()->count(72)->for($notification, 'notification')->create(['status' => 'accepted']);
        PushNotificationDelivery::factory()->count(18)->for($notification, 'notification')->create(['status' => 'accepted', 'first_clicked_at' => now(), 'click_count' => 1]);
        PushNotificationDelivery::factory()->count(10)->for($notification, 'notification')->create(['status' => 'failed', 'error_category' => 'invalid_token']);

        $metrics = app(PushAnalyticsService::class)->summary($notification);
        $this->assertSame(90, $metrics['accepted']);
        $this->assertSame(18, $metrics['unique_clicks']);
        $this->assertSame(20.0, $metrics['ctr']);
        $this->assertSame(10, $metrics['failure_categories']['invalid_token']);
        $this->assertSame(0.0, app(PushAnalyticsService::class)->summary(PushNotification::factory()->create())['ctr']);
    }

    public function test_manual_topic_and_automatic_post_notifications_share_delivery_analytics(): void
    {
        Queue::fake();
        $category = Category::factory()->create();
        app(PushTopicSyncService::class)->sync();
        $topic = PushTopic::query()->where('category_id', $category->id)->firstOrFail();
        $subscription = PushSubscription::factory()->create(['preferences_configured_at' => now()]);
        $subscription->topics()->attach($topic);

        $manual = PushNotification::factory()->create(['target_type' => 'topics']);
        $manual->topics()->attach($topic);
        $this->assertSame(1, app(ManualPushNotificationService::class)->send($manual));
        $this->assertSame(1, $manual->deliveries()->count());

        $post = Post::factory()->published()->create(['push_notified_at' => null]);
        $post->categories()->attach($category);
        config()->set('firebase.automation.enabled', true);
        $this->assertTrue(app(PostPublishPushAutomation::class)->dispatch($post->id));
        $automatic = PushNotification::query()->where(['source_type' => 'post', 'source_id' => $post->id])->firstOrFail();
        $this->assertSame($post->title, $automatic->title);
        $this->assertSame(1, $automatic->deliveries()->count());
    }

    public function test_push_analytics_authorization_is_separate_and_legacy_records_are_safe(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $notification = PushNotification::factory()->create();
        $admin = User::factory()->create()->assignRole('admin');
        $editor = User::factory()->create()->assignRole('editor');
        $analytics = User::factory()->create()->assignRole('analytics-manager');
        $reporter = User::factory()->create()->assignRole('reporter');

        $this->assertTrue(Gate::forUser($admin)->allows('viewAnalytics', $notification));
        $this->assertTrue(Gate::forUser($analytics)->allows('viewAnalytics', $notification));
        $this->assertFalse(Gate::forUser($editor)->allows('viewAnalytics', $notification));
        $this->assertFalse(Gate::forUser($reporter)->allows('viewAnalytics', $notification));
        $this->assertSame(0, app(PushAnalyticsService::class)->summary($notification)['recipients']);
    }

    private function delivery(?string $target = 'https://example.com/news'): PushNotificationDelivery
    {
        $subscription = PushSubscription::factory()->create();
        $notification = PushNotification::factory()->create(['target_url' => $target]);

        return PushNotificationDelivery::factory()->for($notification, 'notification')->for($subscription, 'subscription')->create([
            'subscription_token_hash' => $subscription->token_hash,
        ]);
    }
}

class AnalyticsFakeTransport implements PushTransport
{
    public ?PushMessage $lastMessage = null;

    public function __construct(private readonly PushDeliveryResult $result) {}

    public function send(string $token, PushMessage $message): PushDeliveryResult
    {
        $this->lastMessage = $message;

        return $this->result;
    }
}

class AnalyticsSequenceTransport implements PushTransport
{
    /** @param list<PushDeliveryResult> $results */
    public function __construct(private array $results) {}

    public function send(string $token, PushMessage $message): PushDeliveryResult
    {
        return array_shift($this->results);
    }
}
