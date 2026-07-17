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
        return $user->hasPermissionTo('view posts');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create posts');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('update posts');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('delete posts');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete posts');
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
