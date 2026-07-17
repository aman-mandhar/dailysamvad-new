<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_category_policy_uses_manage_categories_permission(): void
    {
        $category = Category::factory()->create();
        $reporter = User::factory()->create();
        $reporter->assignRole('reporter');

        $this->assertTrue(Gate::forUser($this->admin)->allows('viewAny', Category::class));
        $this->assertTrue(Gate::forUser($this->admin)->allows('create', Category::class));
        $this->assertTrue(Gate::forUser($this->admin)->allows('update', $category));
        $this->assertTrue(Gate::forUser($this->admin)->allows('delete', $category));
        $this->assertFalse(Gate::forUser($reporter)->allows('viewAny', Category::class));
        $this->assertFalse(Gate::forUser($reporter)->allows('update', $category));
    }

    public function test_authorized_user_can_open_category_resource_pages(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->get(CategoryResource::getUrl('index'))->assertOk();
        $this->actingAs($this->admin)->get(CategoryResource::getUrl('create'))->assertOk();
        $this->actingAs($this->admin)
            ->get(CategoryResource::getUrl('edit', ['record' => $category]))
            ->assertOk();
    }

    public function test_category_can_be_created_with_parent_and_display_fields(): void
    {
        $parent = Category::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(CreateCategory::class)
            ->fillForm([
                'parent_id' => $parent->id,
                'name' => 'Punjab News',
                'slug' => 'punjab-news',
                'description' => 'News from across Punjab.',
                'sort_order' => 4,
                'is_active' => true,
                'show_in_menu' => true,
                'meta_title' => 'Punjab News Today',
                'meta_description' => 'The latest news from Punjab.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'parent_id' => $parent->id,
            'name' => 'Punjab News',
            'slug' => 'punjab-news',
            'sort_order' => 4,
            'is_active' => true,
            'show_in_menu' => true,
        ]);
    }

    public function test_slug_must_be_unique(): void
    {
        Category::factory()->create(['slug' => 'politics']);

        Livewire::actingAs($this->admin)
            ->test(CreateCategory::class)
            ->fillForm([
                'name' => 'More Politics',
                'slug' => 'politics',
                'sort_order' => 0,
                'is_active' => true,
                'show_in_menu' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $category = Category::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm(['parent_id' => $category->id])
            ->call('save')
            ->assertHasFormErrors(['parent_id']);
    }

    public function test_category_cannot_be_moved_below_a_descendant(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->child($root)->create();
        $grandchild = Category::factory()->child($child)->create();

        Livewire::actingAs($this->admin)
            ->test(EditCategory::class, ['record' => $root->getRouteKey()])
            ->fillForm(['parent_id' => $grandchild->id])
            ->call('save')
            ->assertHasFormErrors(['parent_id']);

        $this->assertNull($root->refresh()->parent_id);
    }

    public function test_parent_tree_excludes_current_category_and_descendants(): void
    {
        $root = Category::factory()->create(['name' => 'Root']);
        $child = Category::factory()->child($root)->create(['name' => 'Child']);
        $other = Category::factory()->create(['name' => 'Other']);

        $options = CategoryResource::parentOptions($root);

        $this->assertArrayNotHasKey($root->id, $options);
        $this->assertArrayNotHasKey($child->id, $options);
        $this->assertSame('Other', $options[$other->id]);
    }

    public function test_manually_edited_slug_is_preserved_when_name_changes(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCategory::class)
            ->set('data.name', 'Initial Category')
            ->assertSet('data.slug', 'initial-category')
            ->set('data.slug', 'custom-category-slug')
            ->set('data.name', 'Renamed Category')
            ->assertSet('data.slug', 'custom-category-slug');
    }

    public function test_resource_query_counts_posts_without_per_record_queries(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create();
        $post->categories()->attach($category);

        $record = CategoryResource::getEloquentQuery()->findOrFail($category->id);

        $this->assertSame(1, $record->posts_count);
        $this->assertTrue($record->relationLoaded('parent'));
    }
}
