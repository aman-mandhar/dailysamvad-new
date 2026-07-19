<?php

namespace Tests\Feature\Frontend;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SingleNewsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_uses_wordpress_date_url_and_previous_laravel_urls_redirect_permanently(): void
    {
        $post = Post::factory()->published()->create([
            'slug' => 'wordpress-style-story',
            'published_at' => '2026-07-19 10:00:00',
        ]);

        $this->assertSame(url('/2026/07/wordpress-style-story'), $post->publicUrl());
        $this->get($post->publicUrl())->assertOk();
        $this->get('/wordpress-style-story')
            ->assertRedirect($post->publicUrl())
            ->assertStatus(301);
        $this->get('/news/wordpress-style-story')
            ->assertRedirect($post->publicUrl())
            ->assertStatus(301);
    }

    public function test_dated_wordpress_url_requires_the_actual_publication_year_and_month(): void
    {
        $post = Post::factory()->published()->create([
            'slug' => 'dated-wordpress-story',
            'published_at' => '2024-07-10 10:00:00',
        ]);

        $this->get('/2024/07/dated-wordpress-story')->assertOk();
        $this->get('/2025/07/dated-wordpress-story')->assertNotFound();
        $this->get('/2024/08/dated-wordpress-story')->assertNotFound();
        $this->get('/2024/13/dated-wordpress-story')->assertNotFound();
        $this->get('/2024/07/missing-wordpress-story')->assertNotFound();
    }

    public function test_published_article_loads_with_its_relationships(): void
    {
        $category = Category::factory()->create(['name' => 'Punjab']);
        $post = Post::factory()->published()->create(['title' => 'Published public article']);
        $post->categories()->attach($category, ['is_primary' => true]);

        $this->get($post->publicUrl())
            ->assertOk()
            ->assertViewIs('posts.show')
            ->assertSee($post->title)
            ->assertSee($post->author->name)
            ->assertSee($category->name);
    }

    #[DataProvider('unpublishedStatuses')]
    public function test_unpublished_articles_return_not_found(PostStatus $status): void
    {
        $post = Post::factory()->create([
            'status' => $status,
            'published_at' => $status === PostStatus::Published ? now()->addDay() : null,
            'scheduled_at' => $status === PostStatus::Scheduled ? now()->addDay() : null,
        ]);

        $this->get($post->publicUrl())->assertNotFound();
    }

    /** @return array<string, array{PostStatus}> */
    public static function unpublishedStatuses(): array
    {
        return [
            'draft' => [PostStatus::Draft],
            'pending review' => [PostStatus::PendingReview],
            'archived' => [PostStatus::Archived],
            'future scheduled' => [PostStatus::Scheduled],
            'future publication date' => [PostStatus::Published],
        ];
    }

    public function test_related_news_excludes_the_current_article(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->published()->create();
        $related = Post::factory()->published()->create(['title' => 'A related public report']);
        $post->categories()->attach($category, ['is_primary' => true]);
        $related->categories()->attach($category, ['is_primary' => true]);

        $this->get($post->publicUrl())
            ->assertOk()
            ->assertSee('data-related-post="'.$related->getKey().'"', false)
            ->assertDontSee('data-related-post="'.$post->getKey().'"', false);
    }

    public function test_breadcrumb_renders_home_category_and_article(): void
    {
        $category = Category::factory()->create(['name' => 'India']);
        $post = Post::factory()->published()->create(['title' => 'Breadcrumb article']);
        $post->categories()->attach($category, ['is_primary' => true]);

        $this->get($post->publicUrl())
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSeeInOrder(['Home', 'India', 'Breadcrumb article']);
    }

    public function test_stored_seo_fields_and_json_ld_render(): void
    {
        $post = Post::factory()->published()->create([
            'meta_title' => 'Stored SEO title',
            'meta_description' => 'Stored SEO description',
            'canonical_url' => 'https://example.com/canonical-article',
        ]);

        $this->get($post->publicUrl())
            ->assertOk()
            ->assertSee('<title>Stored SEO title</title>', false)
            ->assertSee('content="Stored SEO description"', false)
            ->assertSee('<link rel="canonical" href="https://example.com/canonical-article">', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('NewsArticle');
    }

    public function test_article_html_is_sanitized_before_rendering(): void
    {
        $post = Post::factory()->published()->create([
            'content' => '<p onclick="alert(1)">Safe text</p><script>alert(2)</script><a href="javascript:alert(3)">Unsafe link</a>',
        ]);

        $this->get($post->publicUrl())
            ->assertOk()
            ->assertSee('<p>Safe text</p>', false)
            ->assertSee('Unsafe link')
            ->assertDontSee('onclick=', false)
            ->assertDontSee('<script>alert(2)</script>', false)
            ->assertDontSee('javascript:', false);
    }

    public function test_article_renders_semantics_metadata_tags_share_and_sidebar_once(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Semantic article headline',
            'excerpt' => 'A concise article lead.',
            'content' => '<p>First</p><h2>Inside heading</h2><p>Third</p>',
        ]);
        $tag = Tag::factory()->create(['name' => 'Punjab tag']);
        $post->tags()->attach($tag);

        $response = $this->get($post->publicUrl())->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertSame(1, substr_count($html, '<main'));
        $this->assertSame(1, substr_count($html, 'data-sidebar-context="article"'));
        $response->assertSee('<article class="ds-article"', false)
            ->assertSee('datetime="'.$post->published_at->toIso8601String().'"', false)
            ->assertSee('aria-label="Share on Facebook"', false)
            ->assertSee(route('tags.show', $tag->slug), false);
    }

    public function test_trusted_youtube_embed_and_table_wrapper_render_without_scripts(): void
    {
        $post = Post::factory()->published()->create([
            'content' => '<table><tr><td>Cell</td></tr></table><iframe src="https://www.youtube.com/embed/video"></iframe><iframe src="https://evil.example/embed"></iframe><script>alert(1)</script>',
        ]);

        $this->get($post->publicUrl())
            ->assertOk()
            ->assertSee('class="ds-article-table"', false)
            ->assertSee('youtube.com/embed/video', false)
            ->assertSee('title="Embedded media"', false)
            ->assertDontSee('evil.example', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_soft_deleted_and_missing_articles_return_not_found(): void
    {
        $post = Post::factory()->published()->create();
        $post->delete();

        $this->get($post->publicUrl())->assertNotFound();
        $this->get('/news/not-a-real-article')->assertNotFound();
    }
}
