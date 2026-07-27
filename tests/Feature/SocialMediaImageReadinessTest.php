<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Support\CacheKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SocialMediaImageReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_whitespace_corrupted_slug_redirects_to_clean_canonical_article_url(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'whatsapp-preview-story']);
        Post::withoutEvents(fn () => Post::query()->whereKey($post)->update(['slug' => 'whatsapp preview story ']));

        $dirtyUrl = route('news.show', [
            ...$post->publicRouteParameters(),
            'slug' => 'whatsapp preview story ',
        ]);
        $cleanUrl = route('news.show', [
            ...$post->publicRouteParameters(),
            'slug' => 'whatsapp-preview-story',
        ]);

        $this->get($dirtyUrl)->assertRedirect($cleanUrl)->assertStatus(301);
        $html = $this->get($cleanUrl)->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.$cleanUrl.'">', $html);
        $this->assertStringContainsString('property="og:url" content="'.$cleanUrl.'"', $html);
        $this->assertStringNotContainsString('whatsapp%20preview%20story', $html);
    }

    public function test_new_slug_values_are_trimmed_without_rewriting_valid_imported_characters(): void
    {
        $post = Post::factory()->make(['slug' => '  imported पंजाब story  ']);

        $this->assertSame('imported-पंजाब-story', $post->slug);
    }

    public function test_linked_wordpress_media_wins_and_filament_paths_are_supported(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('wordpress/uploads/linked.jpg', 'linked');
        Storage::disk('public')->put('posts/featured/uploaded.png', 'uploaded');
        $media = Media::query()->create([
            'disk' => 'public',
            'path' => 'wordpress/uploads/linked.jpg',
            'mime_type' => 'image/jpeg',
            'width' => 1200,
            'height' => 630,
        ]);
        $post = Post::factory()->published()->create([
            'featured_media_id' => $media->id,
            'featured_image' => 'posts/featured/uploaded.png',
        ]);

        $html = $this->get($post->publicUrl())->assertOk()->getContent();

        $this->assertStringContainsString('property="og:image" content="http://localhost/storage/wordpress/uploads/linked.jpg"', $html);
        $this->assertStringContainsString('property="og:image:type" content="image/jpeg"', $html);
        $this->assertStringContainsString('property="og:image:width" content="1200"', $html);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
    }

    public function test_direct_filament_upload_exposes_cached_real_mime_and_dimensions(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put(
            'posts/featured/whatsapp-card.png',
            file_get_contents(public_path('images/seo/default-social.png')),
        );
        $post = Post::factory()->published()->create([
            'featured_media_id' => null,
            'featured_image' => 'posts/featured/whatsapp-card.png',
        ]);

        $html = $this->get($post->publicUrl())->assertOk()->getContent();

        $this->assertStringContainsString('property="og:image:type" content="image/png"', $html);
        $this->assertStringContainsString('property="og:image:width" content="1200"', $html);
        $this->assertStringContainsString('property="og:image:height" content="630"', $html);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
    }

    public function test_missing_linked_media_uses_existing_featured_image_then_default(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('posts/featured/existing.png', 'uploaded');
        $media = Media::query()->create([
            'disk' => 'public',
            'path' => 'wordpress/uploads/missing.jpg',
            'missing_at' => null,
        ]);
        $post = Post::factory()->published()->create([
            'featured_media_id' => $media->id,
            'featured_image' => 'posts/featured/existing.png',
        ]);

        $html = $this->get($post->publicUrl())->assertOk()->getContent();
        $this->assertStringContainsString('property="og:image" content="http://localhost/storage/posts/featured/existing.png"', $html);

        $post->update(['featured_image' => 'posts/featured/also-missing.jpg', 'featured_media_id' => null]);
        $html = $this->get($post->fresh()->publicUrl())->assertOk()->getContent();
        $this->assertStringContainsString('property="og:image" content="http://localhost/images/seo/default-social.png"', $html);
        $this->assertStringContainsString('property="og:image:type" content="image/png"', $html);
        $this->assertStringContainsString('property="og:image:width" content="1200"', $html);
        $this->assertStringContainsString('property="og:image:height" content="630"', $html);
    }

    public function test_production_metadata_is_https_unique_and_rejects_local_or_unsafe_sources(): void
    {
        config(['app.env' => 'production', 'app.url' => 'https://news.example.test']);
        $this->app['env'] = 'production';
        URL::forceRootUrl('https://news.example.test');
        URL::forceScheme('https');
        $post = Post::factory()->published()->create([
            'canonical_url' => 'http://news.example.test/story',
            'featured_image' => 'http://127.0.0.1/private.jpg',
            'title' => 'Unsafe <script>alert(1)</script> title',
        ]);

        $html = $this->get($post->publicUrl())->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="https://news.example.test/story">', $html);
        $this->assertStringContainsString('property="og:url" content="https://news.example.test/story"', $html);
        $this->assertStringContainsString('property="og:image" content="https://news.example.test/images/seo/default-social.png"', $html);
        $this->assertStringContainsString('property="og:image:secure_url" content="https://news.example.test/images/seo/default-social.png"', $html);
        $this->assertStringNotContainsString('property="og:image" content="http://127.0.0.1', $html);
        $this->assertStringNotContainsString('name="twitter:image" content="http://127.0.0.1', $html);
        $this->assertSame(1, substr_count($html, '<link rel="canonical"'));
        $this->assertSame(1, substr_count($html, '<meta property="og:image"'));
        $this->assertSame(1, substr_count($html, '<meta name="twitter:image"'));
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_post_and_linked_media_updates_invalidate_cached_article_metadata(): void
    {
        config([
            'cache_architecture.enabled' => true,
            'cache_architecture.query' => true,
            'cache_architecture.store' => 'array',
        ]);
        Storage::fake('public');
        Storage::disk('public')->put('posts/featured/one.jpg', 'one');
        Storage::disk('public')->put('posts/featured/two.jpg', 'two');
        $media = Media::query()->create(['disk' => 'public', 'path' => 'posts/featured/one.jpg']);
        $post = Post::factory()->published()->create(['featured_media_id' => $media->id, 'featured_image' => null]);

        $this->get($post->publicUrl())->assertSee('storage/posts/featured/one.jpg', false);
        $key = app(CacheKey::class)->make('query', 'article', 'public', $post->slug);
        $this->assertTrue(Cache::store('array')->has($key));

        $media->update(['path' => 'posts/featured/two.jpg']);

        $this->assertFalse(Cache::store('array')->has($key));
        $this->get($post->publicUrl())->assertSee('storage/posts/featured/two.jpg', false);
    }
}
