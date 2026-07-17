<?php

namespace Tests\Feature\Frontend;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SingleNewsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_article_loads_with_its_relationships(): void
    {
        $category = Category::factory()->create(['name' => 'Punjab']);
        $post = Post::factory()->published()->create(['title' => 'Published public article']);
        $post->categories()->attach($category, ['is_primary' => true]);

        $this->get(route('news.show', $post->slug))
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

        $this->get(route('news.show', $post->slug))->assertNotFound();
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

        $this->get(route('news.show', $post->slug))
            ->assertOk()
            ->assertSee('data-related-post="'.$related->getKey().'"', false)
            ->assertDontSee('data-related-post="'.$post->getKey().'"', false);
    }

    public function test_breadcrumb_renders_home_category_and_article(): void
    {
        $category = Category::factory()->create(['name' => 'India']);
        $post = Post::factory()->published()->create(['title' => 'Breadcrumb article']);
        $post->categories()->attach($category, ['is_primary' => true]);

        $this->get(route('news.show', $post->slug))
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

        $this->get(route('news.show', $post->slug))
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

        $this->get(route('news.show', $post->slug))
            ->assertOk()
            ->assertSee('<p>Safe text</p>', false)
            ->assertSee('Unsafe link')
            ->assertDontSee('onclick=', false)
            ->assertDontSee('<script>alert(2)</script>', false)
            ->assertDontSee('javascript:', false);
    }
}
