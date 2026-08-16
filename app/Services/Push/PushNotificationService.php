<?php

namespace App\Services\Push;

use App\Contracts\Push\PushTransport;
use App\Data\Push\PushDeliveryResult;
use App\Data\Push\PushMessage;
use App\Jobs\Push\SendPushNotificationJob;
use App\Models\PushNotification;
use App\Models\PushNotificationDelivery;
use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Builder;

class PushNotificationService
{
    public function __construct(private readonly PushTransport $transport) {}

    public function sendToSubscription(PushSubscription $subscription, PushMessage $message): PushDeliveryResult
    {
        if (! config('firebase.messaging.sending_enabled', false)) {
            return PushDeliveryResult::failure('SENDING_DISABLED', 'Outbound push sending is disabled.');
        }
        if (! $subscription->is_active) {
            return PushDeliveryResult::failure('INACTIVE_SUBSCRIPTION', 'The subscription is inactive.');
        }
        if (! is_string($subscription->token) || trim($subscription->token) === '') {
            return PushDeliveryResult::failure('MISSING_TOKEN', 'The subscription has no usable token.', tokenInvalid: true);
        }

        $result = $this->transport->send($subscription->token, $message);
        if ($result->tokenInvalid) {
            $subscription->forceFill([
                'is_active' => false,
                'permission_status' => 'invalid',
                'unsubscribed_at' => now(),
            ])->save();
        }

        return $result;
    }

    public function queueNotification(PushNotification $notification, ?Builder $query = null, ?int $chunkSize = null): int
    {
        $queued = 0;
        $chunkSize ??= max(1, (int) config('firebase.messaging.fanout_chunk_size', 500));
        ($query ?? PushSubscription::query())->active()->select(['id', 'token_hash'])->chunkById($chunkSize, function ($subscriptions) use ($notification, &$queued): void {
            foreach ($subscriptions as $subscription) {
                $delivery = PushNotificationDelivery::query()->firstOrCreate(
                    [
                        'push_notification_id' => $notification->getKey(),
                        'push_subscription_id' => $subscription->getKey(),
                    ],
                    [
                        'subscription_token_hash' => $subscription->token_hash,
                        'status' => 'queued',
                        'queued_at' => now(),
                    ],
                );

                if (! $delivery->wasRecentlyCreated) {
                    continue;
                }

                try {
                    SendPushNotificationJob::dispatch($subscription->getKey(), [], $delivery->getKey());
                    $queued++;
                } catch (\Throwable $exception) {
                    $delivery->delete();
                    throw $exception;
                }
            }
        });

        return $queued;
    }

    public function queueToActiveSubscriptions(PushMessage $message, ?Builder $query = null, ?int $chunkSize = null, ?PushNotification $notification = null): int
    {
        if ($notification !== null) {
            return $this->queueNotification($notification, $query, $chunkSize);
        }

        $queued = 0;
        $chunkSize ??= max(1, (int) config('firebase.messaging.fanout_chunk_size', 500));
        ($query ?? PushSubscription::query())->active()->select('id')->chunkById($chunkSize, function ($subscriptions) use ($message, &$queued): void {
            foreach ($subscriptions as $subscription) {
                SendPushNotificationJob::dispatch($subscription->getKey(), $message->toArray());
                $queued++;
            }
        });

        return $queued;
    }
}
