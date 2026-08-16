<?php

namespace App\Services\Push;

use App\Enums\PostStatus;
use App\Enums\PushNotificationStatus;
use App\Models\Post;
use App\Models\PushNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostPublishPushAutomation
{
    public function __construct(
        private readonly PostPushMessageFactory $messages,
        private readonly PushNotificationService $push,
        private readonly PushAudienceResolver $audiences,
        private readonly PostPushTopicResolver $postTopics,
    ) {}

    public function dispatch(int $postId): bool
    {
        if (! config('firebase.automation.enabled', false)) {
            return false;
        }

        $post = Post::query()->find($postId);
        if ($post === null || $post->old_wp_id !== null || ! $this->isPublic($post)) {
            return false;
        }

        try {
            $message = $this->messages->make($post);
        } catch (Throwable $exception) {
            $this->logFailure($postId, 'message_construction', $exception);

            return false;
        }

        $claimedAt = now();
        $claimed = Post::query()->whereKey($postId)->whereNull('push_notified_at')->update(['push_notified_at' => $claimedAt]);
        if ($claimed !== 1) {
            return false;
        }

        try {
            $notification = PushNotification::query()->firstOrCreate(
                ['source_type' => 'post', 'source_id' => $post->getKey()],
                [
                    'post_id' => $post->getKey(),
                    'title' => $message->title,
                    'body' => $message->body,
                    'image_url' => $message->image,
                    'target_url' => $message->url,
                    'target_type' => 'topics',
                    'status' => PushNotificationStatus::Queued->value,
                    'queued_at' => now(),
                ],
            );
            $notification->topics()->sync($this->postTopics->ids($post));
            $audience = $this->audiences->forPost($post);
            $recipientCount = (clone $audience)->count();
            $queued = $this->push->queueToActiveSubscriptions($message, $audience, null, $notification);
            $notification->forceFill([
                'status' => PushNotificationStatus::Sent,
                'recipient_count' => $recipientCount,
                'queued_at' => $notification->queued_at ?? now(),
                'sent_at' => now(),
            ])->save();

            return true;
        } catch (Throwable $exception) {
            Post::query()->whereKey($postId)->where('push_notified_at', $claimedAt)->update(['push_notified_at' => null]);
            if (isset($notification)) {
                $notification->forceFill([
                    'status' => PushNotificationStatus::Failed,
                    'failed_at' => now(),
                    'failure_message' => 'Broadcast fan-out could not be initiated.',
                ])->save();
            }
            $this->logFailure($postId, 'fanout_dispatch', $exception);

            return false;
        }
    }

    private function isPublic(Post $post): bool
    {
        return $post->status === PostStatus::Published
            && $post->published_at !== null
            && $post->published_at->isPast();
    }

    private function logFailure(int $postId, string $stage, Throwable $exception): void
    {
        Log::warning('Automatic post push dispatch failed safely.', [
            'post_id' => $postId,
            'event' => 'post_published',
            'stage' => $stage,
            'exception' => $exception::class,
        ]);
    }
}
