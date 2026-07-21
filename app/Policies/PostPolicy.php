<?php

namespace App\Policies;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use App\Support\PostWorkflow;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view posts');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('view all posts')
            || ($user->hasPermissionTo('view own posts') && $post->author_id === $user->getKey());
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create posts');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('edit all posts')
            || ($user->hasPermissionTo('edit own posts')
                && $post->author_id === $user->getKey()
                && in_array($post->status, [PostStatus::Draft, PostStatus::Rejected], true));
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('delete all posts')
            || ($user->hasPermissionTo('delete own drafts')
                && $post->author_id === $user->getKey()
                && $post->status === PostStatus::Draft);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete all posts');
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('delete all posts');
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('delete all posts');
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
        return $user->hasPermissionTo('review posts');
    }

    public function publish(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('publish posts')
            && PostWorkflow::canTransition($user, $post->status, PostStatus::Published);
    }

    public function archive(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('publish posts')
            && PostWorkflow::canTransition($user, $post->status, PostStatus::Archived);
    }
}
