<?php

namespace Tests\Feature\Frontend;

use App\Data\AdvertisementData;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Queries\SidebarQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SidebarQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_configuration_order_is_preserved_and_disabled_or_unknown_widgets_are_omitted(): void
    {
        Post::factory()->published()->create();
        config()->set('sidebar.homepage.widgets', [
            ['key' => 'unknown', 'type' => 'mystery', 'enabled' => true],
            ['key' => 'disabled', 'type' => 'latest-news', 'enabled' => false],
            ['key' => 'popular', 'type' => 'popular-news', 'enabled' => true, 'limit' => 1],
            ['key' => 'latest', 'type' => 'latest-news', 'enabled' => true, 'limit' => 1],
        ]);

        $this->assertSame(['popular', 'latest'], app(SidebarQuery::class)->forHomepage()['widgets']->pluck('key')->all());
    }

    public function test_latest_news_uses_publication_rules_limit_and_deterministic_order(): void
    {
        $older = Post::factory()->published()->create(['published_at' => now()->subDay()]);
        $newer = Post::factory()->published()->create(['published_at' => now()->subHour()]);
        Post::factory()->create(['title' => 'Draft']);
        Post::factory()->create(['status' => PostStatus::Published, 'published_at' => now()->addDay()]);
        $this->onlyWidget(['key' => 'latest', 'type' => 'latest-news', 'enabled' => true, 'limit' => 2]);

        $posts = app(SidebarQuery::class)->forHomepage()['widgets']->first()->items;
        $this->assertSame([$newer->id, $older->id], $posts->pluck('id')->all());
        $this->assertTrue($posts->every->relationLoaded('primaryCategory'));
    }

    public function test_popular_news_orders_by_views_then_publication_date_and_respects_limit(): void
    {
        $first = Post::factory()->published()->create(['views_count' => 20, 'published_at' => now()->subDay()]);
        $second = Post::factory()->published()->create(['views_count' => 20, 'published_at' => now()->subDays(2)]);
        Post::factory()->published()->create(['views_count' => 2]);
        $this->onlyWidget(['key' => 'popular', 'type' => 'popular-news', 'enabled' => true, 'limit' => 2]);

        $this->assertSame([$first->id, $second->id], app(SidebarQuery::class)->forHomepage()['widgets']->first()->items->pluck('id')->all());
    }

    public function test_popular_news_falls_back_to_recent_posts_without_view_count_support(): void
    {
        $older = Post::factory()->published()->create(['views_count' => 100, 'published_at' => now()->subDay()]);
        $newer = Post::factory()->published()->create(['views_count' => 1, 'published_at' => now()->subHour()]);
        $this->onlyWidget(['key' => 'popular', 'type' => 'popular-news', 'enabled' => true, 'limit' => 2]);
        $query = new class extends SidebarQuery
        {
            protected function supportsViewCounts(): bool
            {
                return false;
            }
        };

        $this->assertSame([$newer->id, $older->id], $query->forHomepage()['widgets']->first()->items->pluck('id')->all());
    }

    public function test_category_widget_counts_only_published_posts_and_hides_empty_categories(): void
    {
        $category = Category::factory()->create(['name' => 'पंजाब', 'slug' => 'punjab', 'sort_order' => 1]);
        $empty = Category::factory()->create(['sort_order' => 2]);
        $published = Post::factory()->published()->create();
        $draft = Post::factory()->create();
        $future = Post::factory()->create(['status' => PostStatus::Published, 'published_at' => now()->addDay()]);
        $category->posts()->attach([$published->id, $draft->id, $future->id]);
        $this->onlyWidget(['key' => 'categories', 'type' => 'categories', 'enabled' => true, 'limit' => 1, 'show_count' => true]);

        $widget = app(SidebarQuery::class)->forHomepage()['widgets']->first();
        $this->assertCount(1, $widget->items);
        $this->assertSame($category->id, $widget->items->first()->id);
        $this->assertSame(1, $widget->items->first()->published_posts_count);
        $this->assertNotSame($empty->id, $widget->items->first()->id);
    }

    public function test_relationship_and_image_access_after_query_causes_no_database_queries(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->published()->create();
        $category->posts()->attach($post, ['is_primary' => true]);
        $this->onlyWidget(['key' => 'latest', 'type' => 'latest-news', 'enabled' => true, 'limit' => 1]);
        $post = app(SidebarQuery::class)->forHomepage()['widgets']->first()->items->first();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $post->primaryCategory->first()?->name;
        $post->featured_image_url;
        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_advertisement_normalization_and_rendering_supports_html_image_and_placeholder(): void
    {
        $html = AdvertisementData::fromConfig('HTML', ['enabled' => true, 'type' => 'html', 'html' => '<div data-provider="test">Ad</div>'], false);
        $image = AdvertisementData::fromConfig('IMAGE', ['enabled' => true, 'type' => 'image', 'image' => 'https://cdn.test/ad.jpg', 'url' => 'https://advertiser.test', 'width' => 300, 'height' => 250], false);
        $placeholder = AdvertisementData::fromConfig('PLACEHOLDER', ['enabled' => true, 'type' => 'placeholder', 'width' => 300, 'height' => 250], true);

        $this->assertStringContainsString('data-provider="test"', Blade::render('<x-news.advertisement-slot :advertisement="$ad" />', ['ad' => $html]));
        $imageHtml = Blade::render('<x-news.advertisement-slot :advertisement="$ad" />', ['ad' => $image]);
        $this->assertStringContainsString('width="300"', $imageHtml);
        $this->assertStringContainsString('rel="sponsored noopener noreferrer"', $imageHtml);
        $this->assertStringContainsString('ADVERTISEMENT', Blade::render('<x-news.advertisement-slot :advertisement="$ad" />', ['ad' => $placeholder]));
    }

    public function test_disabled_invalid_and_production_placeholder_advertisements_render_nothing(): void
    {
        foreach ([
            AdvertisementData::fromConfig('OFF', ['enabled' => false, 'type' => 'html', 'html' => '<b>Ad</b>'], true),
            AdvertisementData::fromConfig('BAD', ['enabled' => true, 'type' => 'image', 'image' => null], true),
            AdvertisementData::fromConfig('PROD', ['enabled' => true, 'type' => 'placeholder'], false),
        ] as $ad) {
            $this->assertSame('', trim(Blade::render('<x-news.advertisement-slot :advertisement="$ad" />', compact('ad'))));
        }
    }

    public function test_social_widget_uses_only_valid_configured_links(): void
    {
        config()->set('organization.social_links', ['facebook' => 'https://facebook.test/daily', 'x' => null, 'youtube' => 'invalid']);
        $this->onlyWidget(['key' => 'social', 'type' => 'social-follow', 'enabled' => true, 'title' => 'Follow']);
        $widget = app(SidebarQuery::class)->forHomepage()['widgets']->first();
        $this->assertSame(['Facebook'], $widget->items->pluck('label')->all());
    }

    private function onlyWidget(array $widget): void
    {
        config()->set('sidebar.homepage.widgets', [$widget]);
    }
}
