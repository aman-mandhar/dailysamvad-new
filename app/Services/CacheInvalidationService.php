<?php

namespace App\Services;

use App\Models\Post;
use App\Support\CacheKey;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CacheInvalidationService
{
    public function __construct(private readonly CacheKey $keys) {}

    public function invalidatePost(Post|int $post): void
    {
        $id = $post instanceof Post ? $post->getKey() : $post;
        if ($post instanceof Post && filled($post->slug)) {
            try {
                Cache::store(config('cache_architecture.store', 'redis'))->forget(
                    $this->keys->make('query', 'article', 'public', $post->slug),
                );
            } catch (Throwable) {
            }
        }
        $this->bump('post', $id);
        $this->bump('public', 'homepage');
        $this->bump('public', 'archives');
        $this->bump('dashboard', 'metrics');
        $this->invalidateSitemaps();
    }

    public function invalidateTaxonomy(int|string|null $id = null): void { $this->bump('public', 'taxonomy', $id ?? 'all'); $this->bump('public', 'homepage'); $this->invalidateSitemaps(); }
    public function invalidateAuthor(int|string|null $id = null): void { $this->bump('public', 'author', $id ?? 'all'); $this->bump('public', 'homepage'); $this->invalidateSitemaps(); }
    public function invalidateMedia(int|string|null $id = null): void { $this->bump('public', 'media', $id ?? 'all'); $this->bump('public', 'homepage'); $this->invalidateSitemaps(); }
    public function invalidateSeo(int|string|null $id = null): void { $this->bump('seo', 'post', $id ?? 'all'); $this->invalidatePost((int) ($id ?? 0)); }

    public function invalidateSitemaps(): void { try { app(\App\SEO\Sitemap\SitemapCache::class)->invalidate(); } catch (Throwable) {} }

    private function bump(string $resource, string $scope, string|int|null $id = null): void
    {
        try { Cache::store(config('cache_architecture.store', 'redis'))->increment($this->keys->make('version', $resource, $scope, $id ?? 'all')); } catch (Throwable) {}
    }
}
