<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_includes_secondary_assignments_and_excludes_unrelated_or_unpublished_posts(): void
    {
        $category = Category::factory()->create(['name' => 'Punjab', 'description' => '<strong>Punjab updates</strong><script>alert(1)</script>']);
        $secondary = Post::factory()->published()->create(['title' => 'Secondary category report']);
        $draft = Post::factory()->create(['title' => 'Hidden draft']);
        $unrelated = Post::factory()->published()->create(['title' => 'Unrelated report']);
        $category->posts()->attach($secondary, ['is_primary' => false]);
        $category->posts()->attach($draft, ['is_primary' => true]);

        $this->get(route('categories.show', $category->slug))
            ->assertOk()->assertSee('<p class="ds-archive-description">Punjab updates</p>', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('data-archive-post="'.$secondary->id.'"', false)->assertDontSee('data-archive-post="'.$draft->id.'"', false)->assertDontSee('data-archive-post="'.$unrelated->id.'"', false)
            ->assertSee('<link rel="canonical" href="'.route('categories.show', $category->slug).'">', false)
            ->assertSee('CollectionPage')->assertSee('BreadcrumbList');
    }

    public function test_inactive_category_is_not_found_but_valid_empty_category_is_ok(): void
    {
        $inactive = Category::factory()->create(['is_active' => false]);
        $empty = Category::factory()->create();

        $this->get(route('categories.show', $inactive->slug))->assertNotFound();
        $this->get(route('categories.show', $empty->slug))->assertOk()->assertSee('No published news is available in this category.');
    }
}
