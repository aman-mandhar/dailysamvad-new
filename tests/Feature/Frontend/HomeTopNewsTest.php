<?php

namespace Tests\Feature\Frontend;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Queries\BreakingNewsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTopNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticker_contains_only_current_published_breaking_posts(): void
    {
        $breaking = Post::factory()->published()->breaking()->create(['title' => 'Breaking visible']);
        Post::factory()->published()->create(['title' => 'Ordinary report']);
        Post::factory()->breaking()->create(['title' => 'Draft breaking']);
        Post::factory()->breaking()->create(['title' => 'Future breaking', 'status' => PostStatus::Published, 'published_at' => now()->addDay()]);

        $html = $this->get('/')->assertOk()->assertSee($breaking->title)->getContent();
        $ticker = strstr(strstr($html, '<section class="ds-breaking'), '</section>', true);
        $this->assertStringNotContainsString('Ordinary report', $ticker);
        $this->assertStringNotContainsString('Draft breaking', $ticker);
        $this->assertStringNotContainsString('Future breaking', $ticker);
    }

    public function test_breaking_query_eager_loads_required_relations(): void
    {
        Post::factory()->published()->breaking()->create();
        $post = app(BreakingNewsQuery::class)->latest()->firstOrFail();

        $this->assertTrue($post->relationLoaded('author'));
        $this->assertTrue($post->relationLoaded('primaryCategory'));
    }

    public function test_ticker_is_absent_when_empty_and_has_accessible_controls_when_animated(): void
    {
        $this->get('/')->assertDontSee('data-ticker', false);
        Post::factory()->count(2)->published()->breaking()->create();
        $this->get('/')->assertSee('aria-labelledby="breaking-news-heading"', false)->assertSee('data-ticker-toggle', false);
    }

    public function test_hero_contains_the_ten_latest_published_posts_in_order(): void
    {
        $posts = collect(range(1, 12))->map(fn (int $hoursAgo): Post => Post::factory()->published()->create([
            'published_at' => now()->subHours($hoursAgo),
        ]));

        $heroPosts = $this->get('/')->assertOk()->viewData('heroPosts');

        $this->assertCount(10, $heroPosts);
        $this->assertSame($posts->take(10)->pluck('id')->all(), $heroPosts->pluck('id')->all());
    }

    public function test_one_post_renders_static_hero_without_slider_controls(): void
    {
        Post::factory()->published()->create();
        $this->get('/')->assertSee('is-static', false)->assertDontSee('data-slider-next', false);
    }

    public function test_missing_image_uses_existing_placeholder(): void
    {
        Post::factory()->published()->create(['featured_image' => null]);
        $this->get('/')->assertSee('aria-label="No image available"', false)->assertSee('Rzana Punjab');
    }

    public function test_category_badge_is_rendered_only_when_a_primary_category_exists(): void
    {
        $category = Category::factory()->create(['name' => 'Punjab Desk']);
        $withCategory = Post::factory()->published()->featured()->create();
        $category->posts()->attach($withCategory, ['is_primary' => true]);
        Post::factory()->published()->featured()->create();

        $this->get('/')->assertSee('ds-lead-slide__badge', false)->assertSee('Punjab Desk');
    }

    public function test_multi_post_hero_exposes_accessible_navigation_and_pause_controls(): void
    {
        Post::factory()->count(2)->published()->featured()->create();
        $this->get('/')
            ->assertSee('aria-roledescription="carousel"', false)
            ->assertSee('aria-label="Previous top story"', false)
            ->assertSee('data-slider-toggle', false);
    }

    public function test_whatsapp_join_links_render_immediately_below_the_slider(): void
    {
        Post::factory()->published()->featured()->create();

        $response = $this->get('/')->assertOk()
            ->assertSee('https://whatsapp.com/channel/0029VaNmS3h7dmefXnv8T71s', false)
            ->assertSee('https://chat.whatsapp.com/FqbcTOAQUSrBeI1BnZOxOP', false);

        $response->assertSeeInOrder(['data-lead-slider', 'data-whatsapp-join', 'data-push-opt-in', 'latest-news-heading']);
    }
}
