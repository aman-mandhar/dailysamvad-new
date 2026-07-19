<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\EditTag;
use App\Filament\Resources\Tags\TagResource;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class TagResourceTest extends TestCase
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

    public function test_tag_policy_uses_manage_tags_permission(): void
    {
        $tag = Tag::factory()->create();
        $reporter = User::factory()->create();
        $reporter->assignRole('reporter');

        $this->assertTrue(Gate::forUser($this->admin)->allows('viewAny', Tag::class));
        $this->assertTrue(Gate::forUser($this->admin)->allows('create', Tag::class));
        $this->assertTrue(Gate::forUser($this->admin)->allows('update', $tag));
        $this->assertTrue(Gate::forUser($this->admin)->allows('delete', $tag));
        $this->assertFalse(Gate::forUser($reporter)->allows('viewAny', Tag::class));
        $this->assertFalse(Gate::forUser($reporter)->allows('update', $tag));
    }

    public function test_authorized_user_can_open_tag_resource_pages(): void
    {
        $tag = Tag::factory()->create();

        $this->actingAs($this->admin)->get(TagResource::getUrl('index'))->assertOk();
        $this->actingAs($this->admin)->get(TagResource::getUrl('create'))->assertOk();
        $this->actingAs($this->admin)
            ->get(TagResource::getUrl('edit', ['record' => $tag]))
            ->assertOk();
    }

    public function test_tag_can_be_created_with_seo_fields(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateTag::class)
            ->fillForm([
                'name' => 'Punjab Politics',
                'slug' => 'punjab-politics',
                'description' => 'Political news from Punjab.',
                'meta_title' => 'Punjab Politics News',
                'meta_description' => 'Current political reporting and analysis from Punjab.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tags', [
            'name' => 'Punjab Politics',
            'slug' => 'punjab-politics',
            'meta_title' => 'Punjab Politics News',
            'meta_description' => 'Current political reporting and analysis from Punjab.',
        ]);
    }

    public function test_slug_must_be_unique_when_creating_a_tag(): void
    {
        Tag::factory()->create(['slug' => 'elections']);

        Livewire::actingAs($this->admin)
            ->test(CreateTag::class)
            ->fillForm([
                'name' => 'Election Updates',
                'slug' => 'elections',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_name_must_be_unique_when_creating_a_tag(): void
    {
        Tag::factory()->create(['name' => 'Election Updates']);

        Livewire::actingAs($this->admin)
            ->test(CreateTag::class)
            ->fillForm([
                'name' => 'Election Updates',
                'slug' => 'different-election-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    }

    public function test_unicode_name_generates_a_non_empty_unicode_slug(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateTag::class)
            ->set('data.name', 'ਪੰਜਾਬ ਖ਼ਬਰਾਂ')
            ->assertSet('data.slug', 'ਪੰਜਾਬ-ਖ਼ਬਰਾਂ');
    }

    public function test_slug_uniqueness_ignores_the_tag_being_edited(): void
    {
        $tag = Tag::factory()->create(['slug' => 'sports']);

        Livewire::actingAs($this->admin)
            ->test(EditTag::class, ['record' => $tag->getRouteKey()])
            ->fillForm(['name' => 'Sports News', 'slug' => 'sports'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Sports News', $tag->refresh()->name);
    }

    public function test_manually_edited_slug_is_preserved_when_name_changes(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateTag::class)
            ->set('data.name', 'Initial Tag')
            ->assertSet('data.slug', 'initial-tag')
            ->set('data.slug', 'custom-tag-slug')
            ->set('data.name', 'Renamed Tag')
            ->assertSet('data.slug', 'custom-tag-slug');
    }

    public function test_resource_query_counts_posts_without_per_record_queries(): void
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->create();
        $post->tags()->attach($tag);

        $record = TagResource::getEloquentQuery()->findOrFail($tag->id);

        $this->assertSame(1, $record->posts_count);
    }
}
