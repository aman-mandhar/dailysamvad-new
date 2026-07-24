<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;
use App\Support\Authorization\ContentAccess;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['view media', 'manage media']);
    }

    public function view(User $user, Media $media): bool
    {
        return ContentAccess::canAccessMedia($user, $media);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['upload media', 'manage media']);
    }

    public function update(User $user, Media $media): bool
    {
        return ($user->hasPermissionTo('update all media')
            || ($user->hasPermissionTo('update own media') && $media->uploaded_by === $user->getKey())
            || $user->hasPermissionTo('manage media'))
            && ContentAccess::canAccessMedia($user, $media);
    }

    public function restore(User $user, Media $media): bool
    {
        return $this->update($user, $media);
    }

    public function delete(User $user, Media $media): bool
    {
        return ($user->hasPermissionTo('delete all media')
            || ($user->hasPermissionTo('delete own media') && $media->uploaded_by === $user->getKey())
            || $user->hasPermissionTo('manage media'))
            && ContentAccess::canAccessMedia($user, $media)
            && ! $media->featuredPosts()->exists();
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
        return $user->hasAnyPermission(['update all media', 'manage media']);
    }
}
