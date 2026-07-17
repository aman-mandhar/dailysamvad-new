<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('home')
            ->assertSee('Latest News');
    }

    public function test_published_posts_appear(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Visible published report']);

        $this->get('/')
            ->assertOk()
            ->assertSee($post->title);
    }

    public function test_draft_posts_never_appear(): void
    {
        Post::factory()->published()->create(['title' => 'Visible public report']);
        $draft = Post::factory()->create(['title' => 'Private draft report']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Visible public report')
            ->assertDontSee($draft->title);
    }

    public function test_breaking_section_hides_when_empty(): void
    {
        Post::factory()->published()->create();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('id="breaking-news-heading"', false);
    }

    public function test_hero_falls_back_to_latest_published_post(): void
    {
        Post::factory()->published()->create(['published_at' => now()->subDays(2)]);
        $latest = Post::factory()->published()->create(['published_at' => now()->subHour()]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-hero-post="'.$latest->getKey().'"', false);
    }

    public function test_category_blocks_include_only_published_posts(): void
    {
        $category = Category::factory()->create(['name' => 'पंजाब', 'slug' => 'punjab']);
        $published = Post::factory()->published()->create(['title' => 'Published category report']);
        $draft = Post::factory()->create(['title' => 'Draft category report']);
        $category->posts()->attach([$published->id, $draft->id]);

        $this->get('/')
            ->assertOk()
            ->assertSee($category->name)
            ->assertSee($published->title)
            ->assertDontSee($draft->title);
    }
}
