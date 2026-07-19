<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResponsiveImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_verified_candidates_render_with_sizes_and_known_dimensions(): void
    {
        $media = Media::query()->create([
            'disk' => 'public', 'path' => 'media/original.jpg', 'width' => 1200, 'height' => 800,
            'metadata' => ['derivatives' => [
                ['path' => 'media/derived-400.jpg', 'width' => 400, 'verified_at' => now()->toAtomString()],
                ['path' => 'media/unverified-800.jpg', 'width' => 800],
            ]],
        ]);
        $post = Post::factory()->create(['featured_image' => $media->path, 'featured_media_id' => $media->id]);
        $post->setRelation('featuredMedia', $media);

        $html = Blade::render('<x-news.image :post="$post" sizes="50vw" />', compact('post'));

        $this->assertStringContainsString('src="/storage/media/original.jpg"', $html);
        $this->assertStringContainsString('/storage/media/derived-400.jpg 400w', $html);
        $this->assertStringContainsString('/storage/media/original.jpg 1200w', $html);
        $this->assertStringNotContainsString('unverified-800', $html);
        $this->assertStringContainsString('sizes="50vw"', $html);
        $this->assertStringContainsString('width="1200"', $html);
        $this->assertStringContainsString('height="800"', $html);
    }

    public function test_remote_and_unknown_dimension_images_degrade_to_plain_src(): void
    {
        $post = Post::factory()->make(['featured_image' => 'https://images.example.test/news.jpg?v=1']);

        $html = Blade::render('<x-news.image :post="$post" />', compact('post'));

        $this->assertStringContainsString('src="https://images.example.test/news.jpg?v=1"', $html);
        $this->assertStringNotContainsString('srcset=', $html);
        $this->assertStringNotContainsString(' width=', $html);
    }

    public function test_loading_priority_alt_escaping_and_decorative_alt_are_correct(): void
    {
        $post = Post::factory()->make(['featured_image' => 'media/hero.jpg', 'featured_image_alt' => 'समाचार &amp; "विशेष"']);
        $hero = Blade::render('<x-news.hero-card :post="$post" />', compact('post'));
        $decorative = Blade::render('<x-news.responsive-image src="/icon.jpg" alt="" />');

        $this->assertStringContainsString('loading="eager"', $hero);
        $this->assertStringContainsString('fetchpriority="high"', $hero);
        $this->assertStringNotContainsString('loading="lazy"', $hero);
        $this->assertStringContainsString('alt="समाचार &amp;amp; &quot;विशेष&quot;"', $hero);
        $this->assertStringContainsString('alt=""', $decorative);
        $this->assertStringContainsString('loading="lazy"', $decorative);
    }

    public function test_fallback_and_unicode_path_are_safe(): void
    {
        $fallback = Blade::render('<x-news.responsive-image :src="null" />');
        $unicode = Blade::render('<x-news.responsive-image src="/storage/चित्र/समाचार.jpg" alt="चित्र" />');

        $this->assertStringContainsString('No image available', $fallback);
        $this->assertStringContainsString('/storage/चित्र/समाचार.jpg', $unicode);
    }

    public function test_component_and_responsive_data_make_no_database_query(): void
    {
        $post = Post::factory()->make(['featured_image' => 'media/card.jpg']);
        DB::enableQueryLog();
        DB::flushQueryLog();

        Blade::render('<x-news.image :post="$post" />', compact('post'));

        $this->assertCount(0, DB::getQueryLog());
    }
}
