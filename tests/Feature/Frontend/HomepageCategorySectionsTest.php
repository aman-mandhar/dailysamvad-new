<?php

namespace Tests\Feature\Frontend;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Queries\HomepageCategorySectionsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageCategorySectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sections_follow_configured_order_and_layouts(): void
    {
        $punjab = $this->makeSection('पंजाब', 'punjab');
        $this->attach($punjab, Post::factory()->published()->create());
        $this->makeSection('हरियाणा', 'haryana');
        $this->makeSection('बिजनेस', 'business');

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertTrue(strpos($html, 'data-category-section="punjab"') < strpos($html, 'data-category-section="haryana"'));
        $this->assertTrue(strpos($html, 'data-category-section="haryana"') < strpos($html, 'data-category-section="business"'));
        $this->assertStringContainsString('data-category-layout="dual-lead"', $this->sectionHtml($html, 'punjab'));
        $this->assertStringContainsString('data-category-layout="single-lead"', $this->sectionHtml($html, 'haryana'));
        $this->assertStringContainsString('data-category-layout="grid"', $this->sectionHtml($html, 'business'));
    }

    public function test_unpublished_and_future_posts_are_excluded(): void
    {
        $category = Category::factory()->create(['name' => 'पंजाब', 'slug' => 'punjab']);
        $this->attach($category, Post::factory()->published()->create(['title' => 'Visible Punjab']));
        $this->attach($category, Post::factory()->create(['title' => 'Hidden draft']));
        $future = Post::factory()->create(['title' => 'Hidden future', 'status' => PostStatus::Published, 'published_at' => now()->addDay()]);
        $this->attach($category, $future);

        $section = app(HomepageCategorySectionsQuery::class)->get()->firstWhere('key', 'punjab');
        $this->assertSame(['Visible Punjab'], $section['posts']->pluck('title')->all());
    }

    public function test_limits_are_respected(): void
    {
        config()->set('homepage.sections.0.limit', 2);
        $category = Category::factory()->create(['name' => 'पंजाब', 'slug' => 'punjab']);
        Post::factory()->count(4)->published()->create()->each(fn (Post $post) => $this->attach($category, $post));
        $this->assertCount(2, app(HomepageCategorySectionsQuery::class)->get()->firstWhere('key', 'punjab')['posts']);
    }

    public function test_missing_and_empty_categories_do_not_render_wrappers(): void
    {
        Category::factory()->create(['name' => 'पंजाब', 'slug' => 'punjab']);
        $this->get('/')->assertOk()->assertDontSee('data-category-section=', false);
    }

    public function test_one_post_dual_layout_degrades_to_single_lead(): void
    {
        $this->makeSection('पंजाब', 'punjab');
        $html = $this->get('/')->getContent();
        $section = $this->sectionHtml($html, 'punjab');
        $this->assertStringContainsString('data-category-layout="single-lead"', $section);
        $this->assertStringNotContainsString('data-category-layout="dual-lead"', $section);
    }

    public function test_missing_image_uses_existing_fallback_and_view_all_uses_category_route(): void
    {
        $category = Category::factory()->create(['name' => 'पंजाब', 'slug' => 'punjab']);
        $this->attach($category, Post::factory()->published()->create(['featured_image' => null]));
        $this->get('/')->assertSee('aria-label="No image available"', false)->assertSee(route('categories.show', 'punjab'), false);
    }

    public function test_breaking_section_prefers_real_category(): void
    {
        $category = Category::factory()->create(['name' => 'ब्रेकिंग न्यूज़', 'slug' => 'breaking-news']);
        $this->attach($category, Post::factory()->published()->create());
        $section = app(HomepageCategorySectionsQuery::class)->get()->firstWhere('key', 'breaking-news');
        $this->assertSame('category', $section['source']);
    }

    public function test_breaking_section_falls_back_to_breaking_flag_when_category_is_missing(): void
    {
        Post::factory()->published()->breaking()->create();
        $section = app(HomepageCategorySectionsQuery::class)->get()->firstWhere('key', 'breaking-news');
        $this->assertSame('breaking-flag', $section['source']);
        $this->assertCount(1, $section['posts']);
    }

    public function test_slug_resolution_precedes_name_fallback_and_name_fallback_works(): void
    {
        $wrongName = Category::factory()->create(['name' => 'पंजाब', 'slug' => 'not-punjab']);
        $slugMatch = Category::factory()->create(['name' => 'Punjab Region', 'slug' => 'punjab']);
        $this->attach($wrongName, Post::factory()->published()->create(['title' => 'Wrong name post']));
        $this->attach($slugMatch, Post::factory()->published()->create(['title' => 'Slug post']));
        $section = app(HomepageCategorySectionsQuery::class)->get()->firstWhere('key', 'punjab');
        $this->assertSame($slugMatch->id, $section['category']->id);

        $slugMatch->delete();
        $section = app(HomepageCategorySectionsQuery::class)->get()->firstWhere('key', 'punjab');
        $this->assertSame($wrongName->id, $section['category']->id);
    }

    public function test_loaded_posts_have_relationships_ready_without_card_queries(): void
    {
        $this->makeSection('पंजाब', 'punjab');
        $post = app(HomepageCategorySectionsQuery::class)->get()->firstWhere('key', 'punjab')['posts']->first();
        $this->assertTrue($post->relationLoaded('author'));
        $this->assertTrue($post->relationLoaded('primaryCategory'));
    }

    private function makeSection(string $name, string $slug): Category
    {
        $category = Category::factory()->create(compact('name', 'slug'));
        $this->attach($category, Post::factory()->published()->create());

        return $category;
    }

    private function attach(Category $category, Post $post): void
    {
        $category->posts()->attach($post->id, ['is_primary' => true]);
    }

    private function sectionHtml(string $html, string $key): string
    {
        $start = strpos($html, 'data-category-section="'.$key.'"');
        $next = strpos($html, 'data-category-section="', $start + 1);

        return substr($html, $start, $next === false ? null : $next - $start);
    }
}
