<?php

namespace App\Observers;

use App\Models\Media;
use App\Models\Post;
use App\SEO\Sitemap\IndexNowService;
use App\SEO\Sitemap\SitemapCache;
use App\Support\MediaPathNormalizer;
use App\Services\CacheInvalidationService;
use Illuminate\Support\Facades\Storage;

class PostObserver
{
    private const INDEXING_FIELDS = [
        'status', 'published_at', 'slug', 'canonical_url', 'title', 'excerpt', 'content',
        'featured_image', 'featured_media_id', 'featured_image_alt', 'seo_data', 'author_id', 'language',
    ];

    public function updating(Post $post): void
    {
        if ($post->isDirty('featured_image')) {
            $post->rememberFeaturedImageBeforeUpdate($post->getRawOriginal('featured_image'));
        }
    }

    public function updated(Post $post): void
    {
        if ($post->wasChanged(self::INDEXING_FIELDS)) {
            $this->invalidateAndNotify($post);
        }
        if ($post->wasChanged('featured_image')) {
            self::deleteManagedImage($post->pullFeaturedImageBeforeUpdate());
        }
    }

    public function created(Post $post): void
    {
        $this->invalidateAndNotify($post);
    }

    public function deleted(Post $post): void
    {
        $this->invalidateAndNotify($post);
    }

    public function restored(Post $post): void
    {
        $this->invalidateAndNotify($post);
    }

    public function forceDeleted(Post $post): void
    {
        self::deleteManagedImage($post->featured_image);
    }

    private function invalidateAndNotify(Post $post): void
    {
        $cache = app(SitemapCache::class);
        app(CacheInvalidationService::class)->invalidatePost($post);
        if (! $cache->batching()) {
            app(IndexNowService::class)->submit([$post->effectiveCanonicalUrl() ?? $post->publicUrl()]);
        }
    }

    public static function deleteManagedImage(?string $path): bool
    {
        $path = app(MediaPathNormalizer::class)->normalize($path);
        if ($path === null || ! collect((array) config('media.managed_paths', []))->contains(
            fn (string $prefix): bool => str_starts_with($path, trim($prefix, '/').'/'),
        )) {
            return false;
        }

        if (Media::query()->where('disk', config('media.disk', 'public'))->where('path', $path)->exists()) {
            return false;
        }

        if (Post::withTrashed()->where('featured_image', $path)->orWhere('content', 'like', '%'.$path.'%')->exists()) {
            return false;
        }

        return Storage::disk((string) config('media.disk', 'public'))->delete($path);
    }
}
