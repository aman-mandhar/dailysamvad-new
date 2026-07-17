<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Storage;

class PostObserver
{
    public function updating(Post $post): void
    {
        if ($post->isDirty('featured_image')) {
            $post->rememberFeaturedImageBeforeUpdate($post->getRawOriginal('featured_image'));
        }
    }

    public function updated(Post $post): void
    {
        if (! $post->wasChanged('featured_image')) {
            return;
        }

        self::deleteManagedImage($post->pullFeaturedImageBeforeUpdate());
    }

    public function forceDeleted(Post $post): void
    {
        self::deleteManagedImage($post->featured_image);
    }

    public static function deleteManagedImage(?string $path): bool
    {
        if (blank($path) || ! str_starts_with($path, 'posts/featured/')) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }
}
