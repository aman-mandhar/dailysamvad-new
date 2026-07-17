<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_can_be_created(): void
    {
        $category = Category::factory()->create([
            'old_wp_id' => 42,
            'name' => 'Punjab',
            'slug' => 'punjab',
            'sort_order' => 10,
            'is_active' => true,
            'show_in_menu' => false,
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'old_wp_id' => 42,
            'name' => 'Punjab',
            'slug' => 'punjab',
            'sort_order' => 10,
        ]);
        $this->assertTrue($category->is_active);
        $this->assertFalse($category->show_in_menu);
    }

    public function test_slug_must_be_unique(): void
    {
        Category::factory()->create(['slug' => 'politics']);

        $this->expectException(QueryException::class);

        Category::factory()->create(['slug' => 'politics']);
    }

    public function test_old_wp_id_must_be_unique_when_present(): void
    {
        Category::factory()->create(['old_wp_id' => 123]);

        $this->expectException(QueryException::class);

        Category::factory()->create(['old_wp_id' => 123]);
    }

    public function test_parent_and_children_relationships_work(): void
    {
        $parent = Category::factory()->parent()->create();
        $child = Category::factory()->child($parent)->create();

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($parent->children->contains($child));
    }

    public function test_deleting_a_parent_sets_the_child_parent_id_to_null(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->child($parent)->create();

        $parent->delete();

        $this->assertNull($child->refresh()->parent_id);
        $this->assertNull($child->parent);
    }

    public function test_category_scopes_filter_and_order_categories(): void
    {
        $first = Category::factory()->create([
            'name' => 'First',
            'sort_order' => 20,
        ]);
        $second = Category::factory()->create([
            'name' => 'Second',
            'sort_order' => 10,
        ]);
        $inactive = Category::factory()->create([
            'name' => 'Inactive',
            'sort_order' => 1,
            'is_active' => false,
        ]);
        $hidden = Category::factory()->create([
            'name' => 'Hidden',
            'sort_order' => 0,
            'show_in_menu' => false,
        ]);

        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id, $hidden->id],
            Category::query()->active()->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id, $inactive->id],
            Category::query()->menuVisible()->pluck('id')->all(),
        );
        $this->assertSame(
            [$hidden->id, $inactive->id, $second->id, $first->id],
            Category::query()->ordered()->pluck('id')->all(),
        );
    }
}
