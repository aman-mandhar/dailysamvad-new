<?php

namespace Tests\Feature\Frontend;

use App\Models\Post;
use App\Support\MediaUrlResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeaturedImageRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_featured_image_url_accessor_returns_public_storage_url_for_existing_file(): void
    {
        $path = 'wordpress/uploads/2026/06/example.jpg';
        Storage::disk('public')->put($path, 'image');
        $post = Post::factory()->make(['featured_image' => $path]);

        $this->assertSame('/storage/'.$path, $post->featured_image_url);
        $this->assertStringNotContainsString('/public/storage/', $post->featured_image_url);
        $this->assertStringNotContainsString('/storage/storage/', $post->featured_image_url);
        Storage::disk('public')->assertExists($path);
    }

    public function test_accessor_returns_null_for_null_or_malformed_featured_image(): void
    {
        $this->assertNull(Post::factory()->make(['featured_image' => null])->featured_image_url);
        $this->assertNull(Post::factory()->make(['featured_image' => '../missing.jpg'])->featured_image_url);
    }

    public function test_explicit_existence_resolution_returns_null_for_missing_media(): void
    {
        $this->assertNull(app(MediaUrlResolver::class)->resolveExisting('wordpress/uploads/missing.jpg'));
    }

    public function test_placeholder_renders_only_when_featured_image_is_unavailable(): void
    {
        $post = Post::factory()->make(['featured_image' => null]);

        $html = Blade::render('<x-news.image :post="$post" />', compact('post'));

        $this->assertStringContainsString('Daily Samvad', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_hero_renders_existing_image_eagerly_with_seo_alt_text(): void
    {
        $path = 'wordpress/uploads/2026/06/hero.jpg';
        Storage::disk('public')->put($path, 'hero');
        $post = Post::factory()->make([
            'featured_image' => $path,
            'featured_image_alt' => null,
            'meta_title' => 'SEO headline',
            'title' => 'Visible headline',
        ]);

        $html = Blade::render('<x-news.hero-card :post="$post" />', compact('post'));

        $this->assertStringContainsString('/storage/'.$path, $html);
        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertStringContainsString('alt="SEO headline"', $html);
        $this->assertStringNotContainsString('/public/storage/', $html);
        $this->assertStringNotContainsString('/storage/storage/', $html);
    }

    public function test_news_card_renders_existing_image_lazily(): void
    {
        $path = 'wordpress/uploads/2026/06/card.jpg';
        Storage::disk('public')->put($path, 'card');
        $post = Post::factory()->make(['featured_image' => $path]);

        $html = Blade::render('<x-news.medium-card :post="$post" />', compact('post'));

        $this->assertStringContainsString('/storage/'.$path, $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringNotContainsString('Daily Samvad</div>', $html);
    }

    public function test_homepage_uses_accessor_for_hero_and_card_images(): void
    {
        $path = 'wordpress/uploads/2026/06/home.jpg';
        Storage::disk('public')->put($path, 'home');
        Post::factory()->published()->create([
            'featured_image' => $path,
            'is_featured' => true,
            'meta_title' => 'Homepage SEO title',
        ]);

        $response = $this->get('/')->assertOk();

        $response->assertSee('/storage/'.$path, false)
            ->assertSee('loading="eager"', false)
            ->assertSee('loading="lazy"', false)
            ->assertDontSee('/public/storage/', false)
            ->assertDontSee('/storage/storage/', false);
    }
}
