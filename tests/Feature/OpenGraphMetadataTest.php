<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OpenGraphMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_renders_complete_article_open_graph_metadata_once(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('wordpress/uploads/story.webp', 'webp-image');
        config([
            'seo.publisher_url' => 'https://www.facebook.com/dailysamvad',
            'seo.twitter_site' => 'https://x.com/DailySamvad',
        ]);
        $author = User::factory()->create([
            'name' => 'Reporter Name',
            'username' => 'reporter-name',
            'is_public' => true,
            'x_url' => 'https://x.com/reporter_name',
        ]);
        $media = Media::query()->create([
            'disk' => 'public',
            'path' => 'wordpress/uploads/story.webp',
            'mime_type' => 'image/webp',
            'width' => 1200,
            'height' => 675,
            'alt_text' => 'Story social image',
        ]);
        $category = Category::factory()->create(['name' => 'Punjab']);
        $tag = Tag::factory()->create(['name' => 'Election']);
        $post = Post::factory()->published()->create([
            'author_id' => $author->id,
            'featured_media_id' => $media->id,
            'featured_image' => 'wordpress/uploads/story.webp',
            'title' => 'Original <headline>',
            'meta_title' => 'SEO headline',
            'meta_description' => 'SEO article description.',
            'language' => 'pa',
            'seo_data' => ['open_graph' => ['title' => 'Social <headline>', 'description' => 'Social description.']],
        ]);
        $post->categories()->attach($category, ['is_primary' => true]);
        $post->tags()->attach($tag);

        $html = $this->get($post->publicUrl())->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'property="og:title"'));
        $this->assertStringContainsString('property="og:type" content="article"', $html);
        $this->assertStringContainsString('property="og:locale" content="pa_IN"', $html);
        $this->assertStringContainsString('property="og:title" content="Social"', $html);
        $this->assertStringContainsString('property="og:description" content="Social description."', $html);
        $this->assertStringContainsString('property="og:image" content="http://localhost/storage/wordpress/uploads/story.webp"', $html);
        $this->assertStringContainsString('property="og:image:type" content="image/webp"', $html);
        $this->assertStringContainsString('property="og:image:width" content="1200"', $html);
        $this->assertStringContainsString('property="og:image:height" content="675"', $html);
        $this->assertStringContainsString('property="og:image:alt" content="Story social image"', $html);
        $this->assertStringContainsString('property="article:published_time"', $html);
        $this->assertStringContainsString('property="article:modified_time"', $html);
        $this->assertStringContainsString('property="article:author" content="'.route('authors.show', $author->username).'"', $html);
        $this->assertStringContainsString('property="article:publisher" content="https://www.facebook.com/dailysamvad"', $html);
        $this->assertStringContainsString('property="article:section" content="Punjab"', $html);
        $this->assertSame(1, substr_count($html, 'property="article:tag" content="Election"'));
        $this->assertStringNotContainsString($author->email, $html);
    }

    public function test_non_article_pages_use_website_metadata_and_contextual_or_default_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/category-social.jpg', 'category-image');
        $category = Category::factory()->create([
            'name' => 'National',
            'slug' => 'national',
            'image_path' => '/images/category-social.jpg',
        ]);
        $author = User::factory()->create(['avatar_path' => null]);
        $tag = Tag::factory()->create(['slug' => 'politics']);
        $post = Post::factory()->published()->create([
            'author_id' => $author->id,
            'featured_image' => null,
            'featured_image_alt' => null,
            'published_at' => now(),
        ]);
        $post->categories()->attach($category, ['is_primary' => true]);
        $post->tags()->attach($tag);

        $pages = [
            route('home') => '/images/seo/default-social.png',
            route('categories.show', $category->slug) => '/storage/images/category-social.jpg',
            route('tags.show', $tag->slug) => '/images/seo/default-social.png',
            route('authors.show', $author->username) => '/images/seo/default-social.png',
            route('archives.year', now()->year) => '/images/seo/default-social.png',
            route('search', ['q' => '<script>alert(1)</script>']) => '/images/seo/default-social.png',
            route('pages.about') => '/images/seo/default-social.png',
        ];

        foreach ($pages as $url => $imagePath) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('property="og:type" content="website"', $html, $url);
            $this->assertStringContainsString('property="og:image" content="http://localhost'.$imagePath.'"', $html, $url);
            $this->assertSame(1, substr_count($html, 'property="og:title"'), $url);
            $this->assertSame(1, substr_count($html, 'property="og:description"'), $url);
            $this->assertSame(1, substr_count($html, 'property="og:url"'), $url);
            $this->assertSame(1, substr_count($html, '<meta property="og:image"'), $url);
            $this->assertSame(1, substr_count($html, 'name="twitter:card"'), $url);
            $this->assertSame(1, substr_count($html, 'name="twitter:title"'), $url);
            $this->assertStringNotContainsString('<script>alert(1)</script>', $html, $url);
        }
    }

    public function test_unpublished_article_does_not_expose_social_metadata(): void
    {
        $post = Post::factory()->create(['slug' => 'private-draft']);

        $this->get('/'.now()->format('Y/m').'/'.$post->slug)->assertNotFound();
    }
}
