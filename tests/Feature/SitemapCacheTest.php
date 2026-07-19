<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\SEO\Sitemap\SitemapCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SitemapCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_output_is_cached_and_post_changes_target_only_sitemap_version(): void
    {
        config(['seo.sitemaps.cache_ttl' => 3600]);
        Cache::put('unrelated-key', 'preserved', 3600);
        $cache = app(SitemapCache::class);
        $post = Post::factory()->published()->create();
        $before = $cache->version();
        $first = $this->get('/sitemaps/posts-1.xml')->assertOk()->streamedContent();
        $this->assertSame($first, $this->get('/sitemaps/posts-1.xml')->assertOk()->streamedContent());
        $this->assertTrue(Cache::has($cache->key('posts:1')));

        $post->update(['title' => 'Meaningful public update']);

        $this->assertGreaterThan($before, $cache->version());
        $this->assertSame('preserved', Cache::get('unrelated-key'));
        $this->assertNotSame($first, $this->get('/sitemaps/posts-1.xml')->assertOk()->streamedContent());
    }

    public function test_category_and_taxonomy_assignment_invalidate_and_batching_debounces(): void
    {
        $cache = app(SitemapCache::class);
        $post = Post::factory()->published()->create();
        $category = Category::factory()->create();
        $beforeAttach = $cache->version();
        $post->categories()->attach($category, ['is_primary' => true]);
        $this->assertGreaterThan($beforeAttach, $cache->version());

        $beforeBatch = $cache->version();
        $cache->beginBatch();
        $category->update(['name' => 'First update']);
        $category->update(['name' => 'Second update']);
        $this->assertSame($beforeBatch, $cache->version());
        $cache->endBatch();
        $this->assertSame($beforeBatch + 1, $cache->version());
    }
}
