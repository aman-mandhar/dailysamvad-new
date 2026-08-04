<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_sidebar_once_in_configured_order_with_accessible_sections(): void
    {
        Post::factory()->count(2)->published()->create();
        config()->set('sidebar.homepage.widgets', [
            ['key' => 'latest-first', 'type' => 'latest-news', 'enabled' => true, 'title' => 'Latest', 'limit' => 1],
            ['key' => 'popular-second', 'type' => 'popular-news', 'enabled' => true, 'title' => 'Popular', 'limit' => 1],
        ]);

        $html = $this->get('/')->assertOk()->assertSee('data-homepage-sidebar', false)->getContent();
        $this->assertSame(1, substr_count($html, 'data-homepage-sidebar'));
        $this->assertTrue(strpos($html, 'data-sidebar-widget="latest-first"') < strpos($html, 'data-sidebar-widget="popular-second"'));
        $this->assertStringContainsString('aria-labelledby="sidebar-latest-first-heading"', $html);
        $this->assertSame(1, substr_count($html, 'data-mgid-sidebar'));
        $this->assertTrue(strpos($html, 'data-sidebar-widget="popular-second"') < strpos($html, 'data-mgid-sidebar'));
    }

    public function test_homepage_sidebar_preserves_hero_ticker_and_category_sections(): void
    {
        $category = Category::factory()->create(['name' => 'पंजाब', 'slug' => 'punjab']);
        $post = Post::factory()->published()->breaking()->featured()->create();
        $category->posts()->attach($post, ['is_primary' => true]);
        config()->set('sidebar.homepage.widgets', [['key' => 'latest', 'type' => 'latest-news', 'enabled' => true, 'title' => 'Latest', 'limit' => 1]]);

        $this->get('/')
            ->assertSee('data-hero-post', false)
            ->assertSee('data-ticker', false)
            ->assertSee('data-category-section="punjab"', false)
            ->assertSee('data-homepage-sidebar', false);
    }

    public function test_sidebar_news_and_category_routes_and_image_alt_are_rendered(): void
    {
        $category = Category::factory()->create(['name' => 'पंजाब', 'slug' => 'punjab']);
        $post = Post::factory()->published()->create([
            'title' => 'Accessible headline',
            'meta_title' => null,
            'featured_image' => 'https://cdn.test/headline.jpg',
            'featured_image_alt' => null,
        ]);
        $category->posts()->attach($post, ['is_primary' => true]);

        $this->get('/')
            ->assertSee($post->publicUrl(), false)
            ->assertSee(route('categories.show', $category->slug), false)
            ->assertSee('alt="Accessible headline"', false);
    }
}
