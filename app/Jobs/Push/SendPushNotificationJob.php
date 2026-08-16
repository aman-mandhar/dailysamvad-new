<?php

namespace App\Jobs\Push;

use App\Data\Push\PushMessage;
use App\Exceptions\Push\FirebaseConfigurationException;
use App\Exceptions\Push\RetryablePushDeliveryException;
use App\Models\PushNotificationDelivery;
use App\Models\PushSubscription;
use App\Services\Push\PushAnalyticsService;
use App\Services\Push\PushNotificationService;
use App\Services\Push\PushTrackingUrlGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout;

    /** @param array<string, mixed> $message */
    public function __construct(
        public readonly int $subscriptionId,
        public readonly array $message,
        public readonly ?int $deliveryId = null,
    ) {
        $this->tries = max(1, (int) config('firebase.messaging.job_tries', 4));
        $this->timeout = max(1, (int) config('firebase.messaging.job_timeout', 30));
        $this->onQueue((string) config('firebase.messaging.queue', 'push'));
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return array_values(array_filter(
            (array) config('firebase.messaging.job_backoff', [60, 300, 900]),
            fn (mixed $seconds): bool => is_int($seconds) && $seconds > 0,
        ));
    }

    public function handle(PushNotificationService $push, ?PushTrackingUrlGenerator $tracking = null, ?PushAnalyticsService $analytics = null): void
    {
        $tracking ??= app(PushTrackingUrlGenerator::class);
        $analytics ??= app(PushAnalyticsService::class);
        if ($this->deliveryId !== null) {
            $this->handleTracked($push, $tracking, $analytics);

            return;
        }

        $subscription = PushSubscription::query()->active()->find($this->subscriptionId);
        if ($subscription === null) {
            return;
        }

        $result = $push->sendToSubscription($subscription, PushMessage::fromArray($this->message));
        $this->handleRetry($result->retryable, $result->errorCode);
    }

    private function handleTracked(PushNotificationService $push, PushTrackingUrlGenerator $tracking, PushAnalyticsService $analytics): void
    {
        $delivery = PushNotificationDelivery::query()->with(['notification', 'subscription'])->find($this->deliveryId);
        if ($delivery === null || $delivery->status === 'accepted') {
            return;
        }

        $subscription = $delivery->subscription;
        if ($subscription === null || ! $subscription->is_active) {
            PushNotificationDelivery::query()->whereKey($delivery->getKey())->where('status', '!=', 'accepted')->update([
                'status' => 'failed',
                'error_code' => 'INACTIVE_SUBSCRIPTION',
                'error_category' => 'subscription',
                'error_message' => 'The subscription is no longer active.',
                'retryable' => false,
                'failed_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $now = now();
        $claimed = PushNotificationDelivery::query()
            ->whereKey($delivery->getKey())
            ->whereIn('status', ['queued', 'failed'])
            ->update([
                'status' => 'attempting',
                'attempt_count' => DB::raw('attempt_count + 1'),
                'attempted_at' => DB::raw('COALESCE(attempted_at, CURRENT_TIMESTAMP)'),
                'last_attempted_at' => $now,
                'updated_at' => $now,
            ]);
        if ($claimed !== 1) {
            return;
        }

        $delivery->refresh();
        $notification = $delivery->notification;
        $trackingUrl = $tracking->forDelivery($delivery);
        $message = new PushMessage(
            title: $notification->title,
            body: $notification->body,
            image: $notification->image_url,
            url: $trackingUrl,
            data: [
                'type' => $notification->source_type === 'post' ? 'post' : 'manual',
                'entity_id' => (string) $notification->getKey(),
                'tracking_url' => $trackingUrl,
            ],
        );

        try {
            $result = $push->sendToSubscription($subscription, $message);
        } catch (FirebaseConfigurationException $exception) {
            $this->updateClaimed($delivery, [
                'status' => 'failed',
                'error_code' => 'CONFIGURATION_ERROR',
                'error_category' => 'authentication',
                'error_message' => 'Firebase server configuration is unavailable.',
                'retryable' => false,
                'failed_at' => now(),
            ]);
            $this->fail($exception);

            return;
        }

        if ($result->success) {
            $this->updateClaimed($delivery, [
                'status' => 'accepted',
                'fcm_message_id' => $result->messageId,
                'http_status' => $result->httpStatus,
                'error_code' => null,
                'error_category' => null,
                'error_message' => null,
                'retryable' => false,
                'accepted_at' => now(),
                'failed_at' => null,
            ]);

            return;
        }

        $category = $analytics->errorCategory($result->errorCode, $result->tokenInvalid);
        $this->updateClaimed($delivery, [
            'status' => 'failed',
            'http_status' => $result->httpStatus,
            'error_code' => $result->errorCode,
            'error_category' => $category,
            'error_message' => $result->errorMessage,
            'retryable' => $result->retryable,
            'failed_at' => now(),
        ]);

        if (! $result->tokenInvalid) {
            Log::warning('Tracked push delivery attempt failed.', [
                'delivery_id' => $delivery->getKey(),
                'subscription_id' => $subscription->getKey(),
                'error_category' => $category,
                'http_status' => $result->httpStatus,
                'attempt_count' => $delivery->attempt_count,
                'retryable' => $result->retryable,
            ]);
        }
        $this->handleRetry($result->retryable, $result->errorCode);
    }

    /** @param array<string, mixed> $values */
    private function updateClaimed(PushNotificationDelivery $delivery, array $values): void
    {
        PushNotificationDelivery::query()
            ->whereKey($delivery->getKey())
            ->where('status', 'attempting')
            ->update([...$values, 'updated_at' => now()]);
    }

    private function handleRetry(bool $retryable, ?string $errorCode): void
    {
        if ($retryable) {
            throw new RetryablePushDeliveryException($errorCode ?? 'Retryable push delivery failure.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($this->deliveryId === null) {
            return;
        }

        PushNotificationDelivery::query()
            ->whereKey($this->deliveryId)
            ->where('status', '!=', 'accepted')
            ->update([
                'status' => 'failed',
                'error_code' => 'QUEUE_EXHAUSTED',
                'error_category' => 'queue',
                'error_message' => 'Push delivery retries were exhausted.',
                'retryable' => false,
                'failed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
