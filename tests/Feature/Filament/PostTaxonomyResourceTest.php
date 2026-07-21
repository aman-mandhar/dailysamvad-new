<?php

namespace Tests\Feature\Filament;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\PostTaxonomy;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostTaxonomyResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->editor = User::factory()->create();
        $this->editor->assignRole('editor');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_post_can_be_created_with_multiple_categories_and_primary_category(): void
    {
        $primary = Category::factory()->create(['name' => 'National']);
        $secondary = Category::factory()->create(['name' => 'Politics']);

        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm($this->postData([
                'categories' => [$primary->id, $secondary->id],
                'primary_category_id' => $primary->id,
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'taxonomy-news-story')->firstOrFail();

        $this->assertCount(2, $post->categories);
        $this->assertTrue($post->primaryCategory->contains($primary));
        $this->assertFalse($post->primaryCategory->contains($secondary));
    }

    public function test_post_can_be_created_with_multiple_tags(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm($this->postData([
                'categories' => [$category->id],
                'primary_category_id' => $category->id,
                'tags' => $tags->pluck('id')->all(),
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'taxonomy-news-story')->firstOrFail();

        $this->assertCount(2, $post->tags);
        $this->assertEqualsCanonicalizing($tags->pluck('id')->all(), $post->tags->pluck('id')->all());
    }

    public function test_at_least_one_category_and_primary_category_are_required(): void
    {
        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm($this->postData([
                'categories' => [],
                'primary_category_id' => null,
            ]))
            ->call('create')
            ->assertHasFormErrors(['categories', 'primary_category_id']);
    }

    public function test_primary_category_outside_selected_categories_is_rejected(): void
    {
        $selected = Category::factory()->create();
        $outside = Category::factory()->create();

        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm($this->postData([
                'categories' => [$selected->id],
                'primary_category_id' => $outside->id,
            ]))
            ->call('create')
            ->assertHasFormErrors(['primary_category_id']);

        $this->assertDatabaseMissing('posts', ['slug' => 'taxonomy-news-story']);
    }

    public function test_duplicate_category_and_tag_assignments_are_rejected_server_side(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $errors = PostTaxonomy::validate([
            'categories' => [$category->id, $category->id],
            'primary_category_id' => $category->id,
            'tags' => [$tag->id, $tag->id],
        ]);

        $this->assertSame('Duplicate category assignments are not allowed.', $errors['categories']);
        $this->assertSame('Duplicate tag assignments are not allowed.', $errors['tags']);
    }

    public function test_invalid_tag_assignment_is_rejected_server_side(): void
    {
        $category = Category::factory()->create();
        $errors = PostTaxonomy::validate([
            'categories' => [$category->id],
            'primary_category_id' => $category->id,
            'tags' => [999999],
        ]);

        $this->assertSame('Every selected tag must exist.', $errors['tags']);
    }

    public function test_removing_the_current_primary_category_fails_validation(): void
    {
        $primary = Category::factory()->create();
        $remaining = Category::factory()->create();
        $post = Post::factory()->create();
        $post->categories()->attach($primary, ['is_primary' => true]);
        $post->categories()->attach($remaining);

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->set('data.categories', [$remaining->id])
            ->call('save')
            ->assertHasFormErrors(['primary_category_id']);

        $this->assertTrue($post->refresh()->primaryCategory->contains($primary));
    }

    public function test_post_taxonomy_can_be_edited(): void
    {
        $oldCategory = Category::factory()->create();
        $newPrimary = Category::factory()->create();
        $newSecondary = Category::factory()->create();
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $post = Post::factory()->create();
        $post->categories()->attach($oldCategory, ['is_primary' => true]);
        $post->tags()->attach($oldTag);

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->set('data.categories', [$newPrimary->id, $newSecondary->id])
            ->set('data.primary_category_id', $newPrimary->id)
            ->set('data.tags', [$newTag->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();

        $this->assertEqualsCanonicalizing(
            [$newPrimary->id, $newSecondary->id],
            $post->categories->pluck('id')->all(),
        );
        $this->assertTrue($post->primaryCategory->contains($newPrimary));
        $this->assertTrue($post->tags->contains($newTag));
        $this->assertFalse($post->tags->contains($oldTag));
    }

    public function test_category_and_tag_filters_work(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $matching = Post::factory()->create();
        $matching->categories()->attach($category, ['is_primary' => true]);
        $matching->tags()->attach($tag);
        $other = Post::factory()->create();

        Livewire::actingAs($this->editor)
            ->test(ListPosts::class)
            ->filterTable('category', $category->id)
            ->filterTable('tag', $tag->id)
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_resource_query_eager_loads_only_required_table_relationships(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $post = Post::factory()->create();
        $post->categories()->attach($category, ['is_primary' => true]);
        $post->tags()->attach($tag);

        $this->actingAs($this->editor);

        $record = PostResource::getEloquentQuery()->findOrFail($post->id);

        $this->assertTrue($record->relationLoaded('author'));
        $this->assertTrue($record->relationLoaded('primaryCategory'));
        $this->assertTrue($record->relationLoaded('categories'));
        $this->assertTrue($record->relationLoaded('tags'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function postData(array $overrides = []): array
    {
        return [
            'title' => 'Taxonomy News Story',
            'slug' => 'taxonomy-news-story',
            'excerpt' => 'A taxonomy test story.',
            'content' => '<p>Complete taxonomy test content.</p>',
            'language' => 'en',
            'author_id' => $this->editor->id,
            'status' => PostStatus::Draft->value,
            'tags' => [],
            ...$overrides,
        ];
    }
}
