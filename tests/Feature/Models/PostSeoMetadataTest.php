<?php

namespace Tests\Feature\Models;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostSeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_seo_and_source_fields_can_be_stored(): void
    {
        $post = Post::factory()->create([
            'meta_title' => 'Punjab News Today',
            'meta_description' => 'The latest verified news from Punjab.',
            'focus_keyword' => 'Punjab news',
            'canonical_url' => 'https://www.dailysamvad.test/news/punjab-news',
            'old_url' => 'https://legacy.example.com/?p=100',
            'source_url' => 'https://source.example.com/punjab-news',
            'source_name' => 'Example News Agency',
            'source_data' => ['type' => 'website', 'verified' => true],
            'seo_data' => ['robots' => ['index' => true, 'follow' => true]],
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'meta_title' => 'Punjab News Today',
            'focus_keyword' => 'Punjab news',
            'source_name' => 'Example News Agency',
        ]);
    }

    public function test_source_data_is_cast_to_an_array(): void
    {
        $post = Post::factory()->create([
            'source_data' => ['type' => 'website', 'verified' => true],
        ]);

        $this->assertSame(
            ['type' => 'website', 'verified' => true],
            $post->refresh()->source_data,
        );
    }

    public function test_seo_data_is_cast_to_an_array(): void
    {
        $post = Post::factory()->create([
            'seo_data' => ['robots' => ['index' => true, 'follow' => false]],
        ]);

        $this->assertSame(
            ['robots' => ['index' => true, 'follow' => false]],
            $post->refresh()->seo_data,
        );
    }

    public function test_effective_meta_title_uses_meta_title(): void
    {
        $post = Post::factory()->make([
            'title' => 'Original title',
            'meta_title' => 'Optimized SEO title',
        ]);

        $this->assertSame('Optimized SEO title', $post->effectiveMetaTitle());
    }

    public function test_effective_meta_title_falls_back_to_title(): void
    {
        $post = Post::factory()->make([
            'title' => 'Original title',
            'meta_title' => null,
        ]);

        $this->assertSame('Original title', $post->effectiveMetaTitle());
    }

    public function test_effective_meta_description_uses_meta_description(): void
    {
        $post = Post::factory()->make([
            'meta_description' => 'Optimized description',
            'excerpt' => 'Post excerpt',
        ]);

        $this->assertSame('Optimized description', $post->effectiveMetaDescription());
    }

    public function test_effective_meta_description_falls_back_to_excerpt(): void
    {
        $post = Post::factory()->make([
            'meta_description' => null,
            'excerpt' => 'Post excerpt',
        ]);

        $this->assertSame('Post excerpt', $post->effectiveMetaDescription());
    }

    public function test_effective_meta_description_generates_plain_text_from_html_content(): void
    {
        $post = Post::factory()->make([
            'meta_description' => null,
            'excerpt' => null,
            'content' => '<p>Hello <strong>world</strong> &amp; Daily Samvad.</p> <p> Latest news.</p>',
        ]);

        $this->assertSame(
            'Hello world & Daily Samvad. Latest news.',
            $post->effectiveMetaDescription(),
        );
    }

    public function test_generated_meta_description_is_safely_limited_for_multibyte_text(): void
    {
        $post = Post::factory()->make([
            'meta_description' => null,
            'excerpt' => null,
            'content' => '<p>'.str_repeat('ਪੰਜਾਬ ਦੀ ਮਹੱਤਵਪੂਰਨ ਖ਼ਬਰ ', 30).'</p>',
        ]);

        $description = $post->effectiveMetaDescription();

        $this->assertLessThanOrEqual(160, mb_strlen($description));
        $this->assertTrue(mb_check_encoding($description, 'UTF-8'));
        $this->assertStringNotContainsString('<', $description);
    }

    public function test_effective_canonical_url_returns_the_explicit_url(): void
    {
        $post = Post::factory()->make([
            'canonical_url' => 'https://www.dailysamvad.test/news/canonical-story',
        ]);

        $this->assertSame(
            'https://www.dailysamvad.test/news/canonical-story',
            $post->effectiveCanonicalUrl(),
        );
    }

    public function test_effective_canonical_url_returns_null_when_absent(): void
    {
        $post = Post::factory()->make(['canonical_url' => null]);

        $this->assertNull($post->effectiveCanonicalUrl());
    }

    public function test_with_seo_factory_state_populates_seo_fields(): void
    {
        $post = Post::factory()->withSeo()->create();

        $this->assertNotNull($post->meta_title);
        $this->assertNotNull($post->meta_description);
        $this->assertNotNull($post->focus_keyword);
        $this->assertNotNull($post->canonical_url);
        $this->assertIsArray($post->seo_data);
        $this->assertSame('article', $post->seo_data['open_graph']['type']);
    }

    public function test_imported_from_wordpress_factory_state_populates_import_metadata(): void
    {
        $post = Post::factory()->importedFromWordPress()->create();

        $this->assertNotNull($post->old_wp_id);
        $this->assertNotNull($post->old_url);
        $this->assertSame('WordPress', $post->source_name);
        $this->assertSame('wordpress', $post->source_data['platform']);
        $this->assertSame($post->old_wp_id, $post->source_data['post_id']);
        $this->assertSame($post->old_url, $post->source_data['original_url']);
    }

    public function test_nullable_seo_fields_do_not_prevent_post_creation(): void
    {
        $post = Post::factory()->create();

        $this->assertNull($post->meta_title);
        $this->assertNull($post->meta_description);
        $this->assertNull($post->focus_keyword);
        $this->assertNull($post->canonical_url);
        $this->assertNull($post->old_url);
        $this->assertNull($post->source_url);
        $this->assertNull($post->source_name);
        $this->assertNull($post->source_data);
        $this->assertNull($post->seo_data);
    }
}
