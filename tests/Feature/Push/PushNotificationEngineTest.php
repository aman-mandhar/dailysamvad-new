<?php

namespace Tests\Feature\Push;

use App\Contracts\Push\AccessTokenProvider;
use App\Contracts\Push\PushTransport;
use App\Data\Push\PushDeliveryResult;
use App\Data\Push\PushMessage;
use App\Exceptions\Push\FirebaseConfigurationException;
use App\Exceptions\Push\RetryablePushDeliveryException;
use App\Jobs\Push\SendPushNotificationJob;
use App\Models\PushSubscription;
use App\Services\Push\FirebaseAccessTokenProvider;
use App\Services\Push\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PushNotificationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_subscription_is_skipped_without_transport_call(): void
    {
        $transport = new FakePushTransport(PushDeliveryResult::success('unused'));
        $subscription = PushSubscription::factory()->create(['is_active' => false]);

        $result = (new PushNotificationService($transport))->sendToSubscription($subscription, new PushMessage('Title', 'Body'));

        $this->assertSame('INACTIVE_SUBSCRIPTION', $result->errorCode);
        $this->assertSame(0, $transport->calls);
    }

    public function test_unregistered_token_is_deactivated_but_other_failures_are_not(): void
    {
        $subscription = PushSubscription::factory()->create();
        $service = new PushNotificationService(new FakePushTransport(
            PushDeliveryResult::failure('UNREGISTERED', 'Invalid token.', 404, tokenInvalid: true)
        ));

        $service->sendToSubscription($subscription, new PushMessage('Title', 'Body'));
        $this->assertFalse($subscription->fresh()->is_active);
        $this->assertSame('invalid', $subscription->fresh()->permission_status);

        foreach ([
            PushDeliveryResult::failure('UNAUTHENTICATED', 'Auth failed.', 401),
            PushDeliveryResult::failure('UNAVAILABLE', 'Temporary.', 503, retryable: true),
        ] as $failure) {
            $active = PushSubscription::factory()->create();
            (new PushNotificationService(new FakePushTransport($failure)))
                ->sendToSubscription($active, new PushMessage('Title', 'Body'));
            $this->assertTrue($active->fresh()->is_active);
        }
    }

    public function test_multi_subscription_foundation_dispatches_one_job_per_active_subscription(): void
    {
        Queue::fake();
        PushSubscription::factory()->count(3)->create();
        PushSubscription::factory()->create(['is_active' => false]);

        $count = (new PushNotificationService(new FakePushTransport(PushDeliveryResult::success('unused'))))
            ->queueToActiveSubscriptions(new PushMessage('Title', 'Body'), chunkSize: 2);

        $this->assertSame(3, $count);
        Queue::assertPushed(SendPushNotificationJob::class, 3);
    }

    public function test_job_executes_retries_transient_results_and_no_ops_missing_subscription(): void
    {
        $subscription = PushSubscription::factory()->create();
        $successTransport = new FakePushTransport(PushDeliveryResult::success('message-id'));
        (new SendPushNotificationJob($subscription->getKey(), (new PushMessage('Title', 'Body'))->toArray()))
            ->handle(new PushNotificationService($successTransport));
        $this->assertSame(1, $successTransport->calls);

        $missingTransport = new FakePushTransport(PushDeliveryResult::success('unused'));
        (new SendPushNotificationJob(999999, (new PushMessage('Title', 'Body'))->toArray()))
            ->handle(new PushNotificationService($missingTransport));
        $this->assertSame(0, $missingTransport->calls);

        $this->expectException(RetryablePushDeliveryException::class);
        (new SendPushNotificationJob($subscription->getKey(), (new PushMessage('Title', 'Body'))->toArray()))
            ->handle(new PushNotificationService(new FakePushTransport(
                PushDeliveryResult::failure('UNAVAILABLE', 'Temporary.', 503, retryable: true)
            )));
    }

    public function test_missing_configuration_is_controlled_and_test_command_never_broadcasts(): void
    {
        config()->set('firebase.messaging.project_id', null);
        config()->set('firebase.messaging.service_account_path', null);
        $this->expectException(FirebaseConfigurationException::class);
        app(FirebaseAccessTokenProvider::class)->token();
    }

    public function test_test_command_requires_one_explicit_subscription(): void
    {
        $this->app->instance(AccessTokenProvider::class, new FakeAccessTokenProvider);

        $code = Artisan::call('push:test');

        $this->assertSame(2, $code);
        $this->assertStringContainsString('--subscription=<id>', Artisan::output());
    }
}

class FakePushTransport implements PushTransport
{
    public int $calls = 0;

    public function __construct(private readonly PushDeliveryResult $result) {}

    public function send(string $token, PushMessage $message): PushDeliveryResult
    {
        $this->calls++;

        return $this->result;
    }
}
