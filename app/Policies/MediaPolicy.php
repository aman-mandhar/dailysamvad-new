<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage media');
    }

    public function view(User $user, Media $media): bool
    {
        return $user->hasPermissionTo('manage media');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage media');
    }

    public function update(User $user, Media $media): bool
    {
        return $user->hasPermissionTo('manage media');
    }

    public function restore(User $user, Media $media): bool
    {
        return $user->hasPermissionTo('manage media');
    }

    public function delete(User $user, Media $media): bool
    {
        return $user->hasPermissionTo('manage media') && ! $media->featuredPosts()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Media $media): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('manage media');
    }
}
