<?php

namespace App\Services\Push;

use App\Data\Push\PushMessage;
use App\Enums\PushNotificationStatus;
use App\Models\PushNotification;
use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class ManualPushNotificationService
{
    public function __construct(
        private readonly PushNotificationService $push,
        private readonly PushAudienceResolver $audiences,
    ) {}

    public function activeSubscriberCount(): int
    {
        return PushSubscription::query()->active()->count();
    }

    public function send(PushNotification $notification): int
    {
        $this->validateSnapshot($notification);
        $rateKey = 'push:manual-send:'.(auth()->id() ?? 'console');
        $limit = max(1, (int) config('firebase.security.manual_send_limit', 6));
        if (RateLimiter::tooManyAttempts($rateKey, $limit)) {
            throw new RuntimeException('Manual push send rate limit reached. Please wait before trying again.');
        }
        RateLimiter::hit($rateKey, 60);

        $audience = $this->audience($notification);
        $recipientCount = (clone $audience)->count();

        if ($recipientCount === 0) {
            throw new RuntimeException('No active push subscribers are available.');
        }

        $claimed = PushNotification::query()
            ->whereKey($notification->getKey())
            ->where('status', PushNotificationStatus::Draft->value)
            ->update([
                'status' => PushNotificationStatus::Queued->value,
                'recipient_count' => $recipientCount,
                'queued_at' => now(),
                'failed_at' => null,
                'failure_message' => null,
                'updated_at' => now(),
            ]);

        if ($claimed !== 1) {
            throw new RuntimeException('This notification has already been queued or sent.');
        }

        try {
            $queued = $this->push->queueToActiveSubscriptions($this->message($notification), $audience, null, $notification);

            PushNotification::query()
                ->whereKey($notification->getKey())
                ->where('status', PushNotificationStatus::Queued->value)
                ->update([
                    'status' => PushNotificationStatus::Sent->value,
                    'recipient_count' => $queued,
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);

            $notification->refresh();

            return $queued;
        } catch (Throwable $exception) {
            PushNotification::query()
                ->whereKey($notification->getKey())
                ->where('status', PushNotificationStatus::Queued->value)
                ->update([
                    'status' => PushNotificationStatus::Failed->value,
                    'failed_at' => now(),
                    'failure_message' => 'Broadcast fan-out could not be initiated.',
                    'updated_at' => now(),
                ]);

            Log::error('Manual push broadcast initiation failed.', [
                'push_notification_id' => $notification->getKey(),
                'exception' => $exception::class,
            ]);

            $notification->refresh();

            throw new RuntimeException('Push notification could not be queued. Check the push configuration and queue worker.', previous: $exception);
        }
    }

    public function recipientCount(PushNotification $notification): int
    {
        return $this->audience($notification)->count();
    }

    private function audience(PushNotification $notification): Builder
    {
        if (($notification->target_type ?? 'all') === 'all') {
            return $this->audiences->allActive();
        }

        $topicIds = $notification->topics()->active()->pluck('push_topics.id')->all();
        if ($topicIds === []) {
            throw new RuntimeException('Select at least one active push topic.');
        }

        return $this->audiences->forTopics($topicIds, includeLegacy: false);
    }

    public function message(PushNotification $notification): PushMessage
    {
        return new PushMessage(
            title: trim($notification->title),
            body: trim($notification->body),
            image: $notification->image_url,
            url: $notification->target_url,
            data: [
                'type' => 'manual',
                'entity_id' => (string) $notification->getKey(),
            ],
        );
    }

    private function validateSnapshot(PushNotification $notification): void
    {
        Validator::make($notification->only(['title', 'body', 'image_url', 'target_url', 'target_type']), [
            'title' => ['required', 'string', 'max:200', function (string $attribute, mixed $value, \Closure $fail): void {
                if (trim((string) $value) === '') {
                    $fail('The title must contain meaningful text.');
                }
            }],
            'body' => ['required', 'string', 'max:1000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (trim((string) $value) === '') {
                    $fail('The body must contain meaningful text.');
                }
            }],
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'target_url' => ['nullable', 'url:http,https', 'max:2048'],
            'target_type' => ['required', Rule::in(['all', 'topics'])],
        ])->validate();
    }
}
