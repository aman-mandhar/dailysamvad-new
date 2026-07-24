<?php

namespace App\Support\Authorization;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ContentAccess
{
    public static function scopePosts(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->can('view posts')) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('view all posts')) {
            return $query;
        }

        if ($user->can('view own posts') || $user->can('view assigned posts')) {
            return $query->where(function (Builder $query) use ($user): void {
                if ($user->can('view own posts')) {
                    $query->orWhere('author_id', $user->getKey());
                }
                if ($user->can('view assigned posts')) {
                    $query->orWhere('reviewed_by', $user->getKey());
                }
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canAssignPostAuthor(?User $user): bool
    {
        return $user?->can('manage users') ?? false;
    }

    public static function scopeMedia(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->hasAnyPermission(['view media', 'manage media'])) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasAnyPermission(['update all media', 'delete all media']) || $user->can('view all posts')) {
            return $query;
        }

        return $query->where('uploaded_by', $user->getKey());
    }

    public static function canAccessMedia(User $user, Media $media): bool
    {
        return $user->hasAnyPermission(['view media', 'manage media'])
            && ($user->hasAnyPermission(['update all media', 'delete all media'])
                || $user->can('view all posts')
                || $media->uploaded_by === $user->getKey());
    }
}
