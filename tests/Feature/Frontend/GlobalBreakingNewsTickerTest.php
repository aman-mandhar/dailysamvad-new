<?php

namespace Tests\Feature\Frontend;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Queries\BreakingNewsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GlobalBreakingNewsTickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_queries_and_renders_global_ticker_once_before_hero(): void
    {
        $breaking = Post::factory()->published()->breaking()->featured()->create(['title' => 'Global breaking headline']);
        $items = app(BreakingNewsQuery::class)->latest();
        $query = Mockery::mock(BreakingNewsQuery::class);
        $query->shouldReceive('latest')->once()->with(12)->andReturn($items);
        $this->app->instance(BreakingNewsQuery::class, $query);

        $response = $this->get(route('home'))->assertOk()->assertSee($breaking->title)->assertSee('data-hero-post', false);
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, '<section class="ds-breaking'));
        $this->assertLessThan(strpos($html, 'data-hero-post'), strpos($html, '<section class="ds-breaking'));
        $this->assertLessThan(strpos($html, '</header>'), strpos($html, '<section class="ds-breaking'));
    }

    public function test_ticker_renders_once_before_article_and_archive_content(): void
    {
        Post::factory()->published()->breaking()->create(['title' => 'Shared ticker report']);
        $article = Post::factory()->published()->create(['title' => 'Article destination']);
        $category = Category::factory()->create(['name' => 'Punjab']);
        $article->categories()->attach($category, ['is_primary' => true]);

        foreach ([$article->publicUrl(), route('categories.show', $category->slug)] as $url) {
            $response = $this->get($url)->assertOk();
            $html = $response->getContent();
            $this->assertSame(1, substr_count($html, '<section class="ds-breaking'));
            $this->assertLessThan(strpos($html, 'aria-label="Breadcrumb"'), strpos($html, '<section class="ds-breaking'));
            $this->assertSame(1, substr_count($html, '<h1'));
        }
    }

    public function test_ticker_covers_tag_search_date_and_author_archives(): void
    {
        $tag = Tag::factory()->create();
        $author = User::factory()->create(['is_active' => true]);
        Post::factory()->published()->breaking()->create([
            'title' => 'Everywhere ticker report',
            'author_id' => $author->id,
        ]);
        $urls = [
            route('tags.show', $tag->slug),
            route('search'),
            route('search', ['q' => 'news']),
            route('archives.year', 2024),
            route('archives.month', [2024, 2]),
            route('archives.day', [2024, 2, 29]),
            route('authors.show', $author->username),
        ];

        foreach ($urls as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, '<section class="ds-breaking'), $url);
        }
    }

    public function test_empty_or_disabled_ticker_renders_no_wrapper(): void
    {
        $this->get(route('search'))->assertOk()->assertDontSee('<section class="ds-breaking', false);

        Post::factory()->published()->breaking()->create();
        config()->set('frontend.breaking_news.enabled', false);
        $this->get(route('home'))->assertOk()->assertDontSee('<section class="ds-breaking', false);
    }

    public function test_error_pages_do_not_receive_global_ticker(): void
    {
        Post::factory()->published()->breaking()->create();

        $this->get('/definitely-not-a-public-page')->assertNotFound()->assertDontSee('<section class="ds-breaking', false);
    }

    public function test_ticker_reuses_publication_filtering_and_eager_loads_relationships(): void
    {
        $published = Post::factory()->published()->breaking()->create(['title' => 'Visible breaking report']);
        Post::factory()->breaking()->create(['title' => 'Draft breaking report']);
        Post::factory()->breaking()->create(['title' => 'Future breaking report', 'status' => PostStatus::Published, 'published_at' => now()->addDay()]);
        $deleted = Post::factory()->published()->breaking()->create(['title' => 'Deleted breaking report']);
        $deleted->delete();

        $items = app(BreakingNewsQuery::class)->latest();

        $this->assertSame([$published->id], $items->modelKeys());
        $this->assertTrue($items->every->relationLoaded('author'));
        $this->assertTrue($items->every->relationLoaded('primaryCategory'));
    }

    public function test_ticker_preserves_accessibility_and_reduced_motion_contract(): void
    {
        Post::factory()->published()->breaking()->count(2)->create();
        $response = $this->get(route('home'))->assertOk()
            ->assertSee('aria-labelledby="breaking-news-heading"', false)
            ->assertSee('data-ticker-toggle', false)
            ->assertSee('ds-breaking__control sr-only', false)
            ->assertSee('Pause breaking news');

        $this->assertSame(1, substr_count($response->getContent(), 'id="breaking-news-heading"'));
        $css = file_get_contents(resource_path('css/frontend/home-top.css'));
        $this->assertStringContainsString('@media(prefers-reduced-motion:reduce)', $css);
    }
}
