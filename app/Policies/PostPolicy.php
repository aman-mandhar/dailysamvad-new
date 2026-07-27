<?php

namespace App\Policies;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_active ? null : false;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view posts');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('view all posts')
            || ($user->hasPermissionTo('view own posts') && $post->author_id === $user->getKey())
            || ($user->hasPermissionTo('view assigned posts') && $post->reviewed_by === $user->getKey());
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create posts');
    }

    public function update(User $user, Post $post): bool
    {
        return ($user->hasAnyRole(['super-admin', 'admin'])
                && $user->hasAnyPermission(['update all posts', 'edit all posts']))
            || ($user->hasRole('editor') && $post->author_id === $user->getKey())
            || ($user->hasAnyPermission(['update own posts', 'edit own posts'])
                && $post->author_id === $user->getKey()
                && in_array($post->status, [PostStatus::Draft, PostStatus::ChangesRequested], true))
            || ($user->hasPermissionTo('update assigned posts')
                && $post->reviewed_by === $user->getKey()
                && $post->status === PostStatus::PendingReview);
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasAnyPermission(['delete all posts', 'delete posts'])
            || ($user->hasAnyPermission(['delete own posts', 'delete own drafts'])
                && $post->author_id === $user->getKey()
                && $post->status === PostStatus::Draft);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnyPermission(['delete all posts', 'delete posts']);
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->hasAnyPermission(['restore posts', 'delete all posts']);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasAnyPermission(['restore posts', 'delete all posts']);
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->hasRole('super-admin');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function replicate(User $user, Post $post): bool
    {
        return false;
    }

    public function review(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('review posts') && $this->view($user, $post);
    }

    public function submitForReview(User $user, Post $post): bool
    {
        return $post->author_id === $user->getKey()
            && in_array($post->status, [PostStatus::Draft, PostStatus::ChangesRequested], true)
            && $user->hasAnyPermission(['submit posts for review', 'submit own posts']);
    }

    public function requestCorrections(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('request post corrections') && $this->review($user, $post);
    }

    public function startReview(User $user, Post $post): bool
    {
        return $post->status === PostStatus::PendingReview && $this->review($user, $post);
    }

    public function assignReviewer(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('assign reviewers')
            && $post->status === PostStatus::PendingReview
            && $user->hasPermissionTo('view all posts');
    }

    public function approve(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('approve posts') && $this->review($user, $post);
    }

    public function reject(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('reject posts') && $this->review($user, $post);
    }

    public function schedule(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('schedule posts')
            && in_array($post->status, [PostStatus::Approved, PostStatus::Scheduled], true);
    }

    public function publish(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('publish posts')
            && in_array($post->status, [PostStatus::Approved, PostStatus::Scheduled], true);
    }

    public function archive(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('archive posts') && $post->status === PostStatus::Published;
    }

    public function restoreWorkflow(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('restore posts')
            && in_array($post->status, [PostStatus::Rejected, PostStatus::Archived], true);
    }

    public function viewWorkflowHistory(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('view workflow history') && $this->view($user, $post);
    }

    public function manageSeo(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('manage seo') && $this->view($user, $post);
    }

    public function viewAnalytics(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('view all analytics')
            || ($user->hasPermissionTo('view editorial analytics') && $user->hasPermissionTo('view all posts'))
            || ($user->hasPermissionTo('view own analytics') && $post->author_id === $user->getKey());
    }
}
