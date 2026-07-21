<?php

namespace App\Support;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostWorkflow
{
    public static function canTransition(User $actor, PostStatus $from, PostStatus $to): bool
    {
        if ($from === $to) {
            return $actor->hasAnyPermission(['edit own posts', 'edit all posts', 'update posts']);
        }

        if ($actor->hasPermissionTo('manage roles')) {
            return true;
        }

        return match ([$from, $to]) {
            [PostStatus::Draft, PostStatus::PendingReview] => $actor->hasPermissionTo('submit own posts'),
            [PostStatus::PendingReview, PostStatus::Published],
            [PostStatus::PendingReview, PostStatus::Scheduled],
            [PostStatus::Scheduled, PostStatus::Published],
            [PostStatus::Published, PostStatus::Archived] => $actor->hasPermissionTo('publish posts'),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public static function validate(User $actor, PostStatus $from, array $data): array
    {
        $errors = [];
        $to = self::statusFrom($data['status'] ?? null);

        if ($to === null) {
            $errors['status'] = 'Select a valid post status.';

            return $errors;
        }

        if (! self::canTransition($actor, $from, $to)) {
            $errors['status'] = "The transition from {$from->value} to {$to->value} is not allowed.";
        }

        $scheduledAt = $data['scheduled_at'] ?? null;

        if (filled($scheduledAt) && Carbon::parse($scheduledAt)->isPast()) {
            $errors['scheduled_at'] = 'The scheduled time must be in the future.';
        }

        if ($to === PostStatus::Scheduled && blank($scheduledAt)) {
            $errors['scheduled_at'] = 'A future scheduled time is required for scheduled posts.';
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareForPersistence(array $data, ?Post $post = null): array
    {
        $status = self::statusFrom($data['status'] ?? null);

        if ($post?->published_at !== null) {
            $data['published_at'] = $post->published_at;
        } elseif ($status === PostStatus::Published && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        return $data;
    }

    public static function transition(User $actor, Post $post, PostStatus $to): void
    {
        DB::transaction(function () use ($actor, $post, $to): void {
            $current = Post::query()->lockForUpdate()->findOrFail($post->getKey());
            $errors = self::validate($actor, $current->status, [
                'status' => $to->value,
                'scheduled_at' => $current->scheduled_at,
            ]);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $data = self::prepareForPersistence(['status' => $to->value], $current);
            $current->update($data);
            $post->setRawAttributes($current->getAttributes(), true);
        });
    }

    private static function statusFrom(mixed $status): ?PostStatus
    {
        if ($status instanceof PostStatus) {
            return $status;
        }

        return is_string($status) ? PostStatus::tryFrom($status) : null;
    }
}
