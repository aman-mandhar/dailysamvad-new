<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Exceptions\InvalidPostTransition;
use App\Models\Post;
use App\Models\PostWorkflowEvent;
use App\Models\User;
use App\Notifications\EditorialWorkflowNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditorialWorkflowService
{
    public function changeStatus(Post $post, User $actor, PostStatus $to, Carbon|string|null $scheduledAt = null): Post
    {
        Gate::forUser($actor)->authorize('update', $post);

        if (! $actor->hasAnyRole(['super-admin', 'admin', 'editor'])) {
            throw new AuthorizationException('You are not allowed to change post status.');
        }

        $schedule = $scheduledAt !== null ? Carbon::parse($scheduledAt) : null;
        if ($to === PostStatus::Scheduled && ($schedule === null || ! $schedule->isFuture())) {
            throw ValidationException::withMessages([
                'data.scheduled_at' => 'A future scheduled time is required for scheduled posts.',
            ]);
        }

        return DB::transaction(function () use ($post, $actor, $to, $schedule): Post {
            $current = $this->locked($post);

            if ($current->status !== $post->status) {
                throw ValidationException::withMessages([
                    'data.status' => 'The post status changed after this page was loaded. Refresh and try again.',
                ]);
            }

            $from = $current->status;
            $attributes = ['status' => $to];

            if ($to === PostStatus::Published) {
                $attributes['published_at'] = $current->published_at ?? now();
                $attributes['published_by'] = $actor->getKey();
            }

            if ($to === PostStatus::Scheduled) {
                $attributes['scheduled_at'] = $schedule;
                $attributes['scheduled_by'] = $actor->getKey();
            } else {
                $attributes['scheduled_at'] = null;
                $attributes['scheduled_by'] = null;
            }

            if ($to === PostStatus::Archived) {
                $attributes['archived_at'] = now();
                $attributes['archived_by'] = $actor->getKey();
            } elseif ($from === PostStatus::Archived) {
                $attributes['archived_at'] = null;
                $attributes['archived_by'] = null;
            }

            if ($from === $to && ($to !== PostStatus::Scheduled || $current->scheduled_at?->equalTo($schedule))) {
                return $current;
            }

            $current->forceFill($attributes)->save();
            $this->event($current, $actor, 'status_changed', $from, $to, metadata: ['source' => 'edit_form']);

            return $current;
        });
    }

    public function submitForReview(Post $post, User $actor): Post
    {
        $this->validateSubmission($post);

        return $this->transition($post, $actor, [PostStatus::Draft, PostStatus::ChangesRequested], PostStatus::PendingReview, 'submitted', [
            'submitted_at' => now(), 'submitted_by' => $actor->getKey(),
            'correction_notes' => null,
        ], ability: 'submitForReview', notify: $this->editors());
    }

    public function assignReviewer(Post $post, User $reviewer, User $actor): Post
    {
        Gate::forUser($actor)->authorize('assignReviewer', $post);
        if (! $reviewer->is_active || ! $reviewer->can('review posts')) {
            throw new InvalidPostTransition('The selected reviewer is not active and eligible.');
        }

        return DB::transaction(function () use ($post, $reviewer, $actor): Post {
            $current = $this->locked($post);
            if ($current->status !== PostStatus::PendingReview) {
                throw new InvalidPostTransition('Only pending-review posts can be assigned.');
            }
            if ((int) $current->reviewed_by === (int) $reviewer->getKey()) {
                return $current;
            }
            $previous = $current->reviewed_by;
            $current->forceFill(['reviewed_by' => $reviewer->getKey(), 'review_assigned_at' => now()])->save();
            $this->event($current, $actor, $previous ? 'reviewer_reassigned' : 'reviewer_assigned', $current->status, $current->status, null, [
                'previous_reviewer_id' => $previous, 'reviewer_id' => $reviewer->getKey(),
            ]);
            DB::afterCommit(fn () => $this->notify([$reviewer], $current, 'reviewer_assigned'));

            return $current;
        });
    }

    public function requestCorrections(Post $post, User $actor, string $notes): Post
    {
        $notes = $this->requiredNotes($notes, 'Correction notes are required.');

        return $this->transition($post, $actor, [PostStatus::PendingReview, PostStatus::Approved], PostStatus::ChangesRequested, 'corrections_requested', [
            'corrections_requested_at' => now(), 'corrections_requested_by' => $actor->getKey(), 'correction_notes' => $notes,
            'reviewed_at' => now(),
        ], $notes, 'requestCorrections', $this->author($post));
    }

    public function startReview(Post $post, User $actor): Post
    {
        Gate::forUser($actor)->authorize('startReview', $post);

        return DB::transaction(function () use ($post, $actor): Post {
            $current = $this->locked($post);
            if ($current->status !== PostStatus::PendingReview) {
                throw new InvalidPostTransition('Only pending-review posts can enter review.');
            }
            if ($current->review_started_at) {
                return $current;
            }
            $current->forceFill(['review_started_at' => now()])->save();
            $this->event($current, $actor, 'review_started', $current->status, $current->status);

            return $current;
        });
    }

    public function approve(Post $post, User $actor, ?string $notes = null): Post
    {
        return $this->transition($post, $actor, [PostStatus::PendingReview], PostStatus::Approved, 'approved', [
            'approved_at' => now(), 'approved_by' => $actor->getKey(), 'reviewed_at' => now(), 'review_notes' => $notes,
        ], $notes, 'approve', $this->author($post));
    }

    public function reject(Post $post, User $actor, string $reason): Post
    {
        $reason = $this->requiredNotes($reason, 'A rejection reason is required.');

        return $this->transition($post, $actor, [PostStatus::PendingReview], PostStatus::Rejected, 'rejected', [
            'rejected_at' => now(), 'rejected_by' => $actor->getKey(), 'rejection_reason' => $reason, 'reviewed_at' => now(),
        ], $reason, 'reject', $this->author($post));
    }

    public function reopen(Post $post, User $actor): Post
    {
        return $this->transition($post, $actor, [PostStatus::Rejected, PostStatus::Archived], PostStatus::Draft, 'restored', [
            'rejected_at' => null, 'rejected_by' => null, 'archived_at' => null, 'archived_by' => null,
        ], ability: 'restoreWorkflow');
    }

    public function schedule(Post $post, User $actor, Carbon|string $at): Post
    {
        $at = Carbon::parse($at);
        if (! $at->isFuture()) {
            throw new InvalidPostTransition('The scheduled time must be in the future.');
        }

        return $this->transition($post, $actor, [PostStatus::Approved, PostStatus::Scheduled], PostStatus::Scheduled,
            $post->status === PostStatus::Scheduled ? 'rescheduled' : 'scheduled',
            ['scheduled_at' => $at, 'scheduled_by' => $actor->getKey()], ability: 'schedule', notify: $this->author($post));
    }

    public function cancelSchedule(Post $post, User $actor): Post
    {
        return $this->transition($post, $actor, [PostStatus::Scheduled], PostStatus::Approved, 'schedule_cancelled', [
            'scheduled_at' => null, 'scheduled_by' => null,
        ], ability: 'schedule');
    }

    public function publish(Post $post, User $actor): Post
    {
        return $this->transition($post, $actor, [PostStatus::Approved, PostStatus::Scheduled], PostStatus::Published, 'published', [
            'published_at' => now(), 'published_by' => $actor->getKey(), 'scheduled_at' => null,
        ], ability: 'publish', notify: $this->author($post));
    }

    public function publishScheduled(Post $post): Post
    {
        return DB::transaction(function () use ($post): Post {
            $current = $this->locked($post);
            if ($current->status === PostStatus::Published) {
                return $current;
            }
            if ($current->status !== PostStatus::Scheduled || ! $current->scheduled_at || $current->scheduled_at->isFuture()) {
                throw new InvalidPostTransition('The post is not due for scheduled publication.');
            }
            $from = $current->status;
            $current->forceFill(['status' => PostStatus::Published, 'published_at' => now(), 'published_by' => null, 'scheduled_at' => null])->save();
            $this->event($current, null, 'published', $from, PostStatus::Published, metadata: ['source' => 'scheduler']);
            DB::afterCommit(fn () => $this->notify($this->author($current), $current, 'published'));

            return $current;
        });
    }

    public function archive(Post $post, User $actor): Post
    {
        return $this->transition($post, $actor, [PostStatus::Published], PostStatus::Archived, 'archived', [
            'archived_at' => now(), 'archived_by' => $actor->getKey(),
        ], ability: 'archive');
    }

    private function transition(Post $post, User $actor, array $from, PostStatus $to, string $event, array $attributes = [], ?string $notes = null, ?string $ability = null, array $notify = []): Post
    {
        if ($ability) {
            Gate::forUser($actor)->authorize($ability, $post);
        }

        return DB::transaction(function () use ($post, $actor, $from, $to, $event, $attributes, $notes, $notify): Post {
            $current = $this->locked($post);
            if (! in_array($current->status, $from, true)) {
                throw new InvalidPostTransition("Invalid transition from {$current->status->value} to {$to->value}.");
            }
            if ($current->status === $to && $event !== 'rescheduled') {
                return $current;
            }
            $old = $current->status;
            $current->forceFill([...$attributes, 'status' => $to])->save();
            $this->event($current, $actor, $event, $old, $to, $notes);
            if ($notify !== []) {
                DB::afterCommit(fn () => $this->notify($notify, $current, $event, $notes));
            }

            return $current;
        });
    }

    private function validateSubmission(Post $post): void
    {
        if (blank($post->title) || blank($post->slug) || blank($post->content) || ! $post->author_id || ! $post->categories()->exists()) {
            throw new InvalidPostTransition('A title, slug, content, author, and category are required before submission.');
        }
    }

    private function event(Post $post, ?User $actor, string $event, ?PostStatus $from, ?PostStatus $to, ?string $notes = null, ?array $metadata = null): void
    {
        PostWorkflowEvent::query()->create(['post_id' => $post->getKey(), 'actor_id' => $actor?->getKey(), 'event' => $event, 'from_status' => $from?->value, 'to_status' => $to?->value, 'notes' => $notes, 'metadata' => $metadata]);
    }

    private function locked(Post $post): Post
    {
        return Post::query()->lockForUpdate()->findOrFail($post->getKey());
    }

    private function requiredNotes(string $notes, string $message): string
    {
        $notes = trim($notes);
        if ($notes === '') {
            throw new InvalidPostTransition($message);
        }

        return $notes;
    }

    private function author(Post $post): array
    {
        $author = $post->author()->where('is_active', true)->first();

        return $author ? [$author] : [];
    }

    private function editors(): array
    {
        return User::query()->where('is_active', true)->permission('review posts')->get()->all();
    }

    private function notify(array $users, Post $post, string $event, ?string $message = null): void
    {
        foreach ($users as $user) {
            try {
                $user->notify(new EditorialWorkflowNotification($post, $event, $message));
            } catch (Throwable $e) {
                Log::warning('Editorial notification failed.', ['event' => $event, 'exception' => $e::class]);
            }
        }
    }
}
