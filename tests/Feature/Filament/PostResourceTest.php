<?php

namespace Tests\Feature\Filament;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Support\Authorization\ContentAccess;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->editor = User::factory()->create();
        $this->editor->assignRole('editor');
        $this->category = Category::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_post_policy_uses_post_permissions(): void
    {
        $post = Post::factory()->create(['reviewed_by' => User::factory()->create()]);
        $reviewer = User::factory()->create();
        $reviewer->assignRole('reviewer');

        $this->assertTrue(Gate::forUser($this->editor)->allows('viewAny', Post::class));
        $this->assertTrue(Gate::forUser($this->editor)->allows('view', $post));
        $this->assertTrue(Gate::forUser($this->editor)->allows('create', Post::class));
        $this->assertTrue(Gate::forUser($this->editor)->allows('update', $post));
        $this->assertFalse(Gate::forUser($this->editor)->allows('delete', $post));
        $this->assertFalse(Gate::forUser($reviewer)->allows('view', $post));
        $this->assertFalse(Gate::forUser($reviewer)->allows('create', Post::class));
        $this->assertFalse(Gate::forUser($reviewer)->allows('update', $post));
    }

    public function test_authorized_user_can_open_post_resource_pages(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($this->editor)->get(PostResource::getUrl('index'))->assertOk();
        $this->actingAs($this->editor)->get(PostResource::getUrl('create'))->assertOk();
        $this->actingAs($this->editor)
            ->get(PostResource::getUrl('edit', ['record' => $post]))
            ->assertOk();
    }

    public function test_user_without_view_permission_cannot_access_post_resource(): void
    {
        Role::findByName('editor')->revokePermissionTo('view posts');

        $this->actingAs($this->editor)
            ->get(PostResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_post_can_be_created_with_author_status_and_language(): void
    {
        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm([
                'title' => 'Punjab Assembly Reviews New Education Plan',
                'slug' => 'punjab-assembly-education-plan',
                'excerpt' => 'The assembly reviewed a proposed education plan.',
                'content' => '<p>Members discussed the proposed education plan during the session.</p>',
                'language' => 'pa',
                'author_id' => $this->editor->id,
                'status' => PostStatus::PendingReview->value,
                'categories' => [$this->category->id],
                'primary_category_id' => $this->category->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'punjab-assembly-education-plan')->firstOrFail();

        $this->assertTrue($post->author->is($this->editor));
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertSame('pa', $post->language);
    }

    public function test_post_can_be_edited(): void
    {
        $post = Post::factory()->create(['status' => PostStatus::Draft, 'language' => 'hi']);
        $post->categories()->attach($this->category, ['is_primary' => true]);

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'title' => 'Updated News Headline',
                'excerpt' => 'Updated summary.',
                'content' => '<p>Updated article content.</p>',
                'status' => PostStatus::PendingReview->value,
                'language' => 'en',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();

        $this->assertSame('Updated News Headline', $post->title);
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertSame('en', $post->language);
    }

    public function test_slug_must_be_unique_and_ignores_the_current_post(): void
    {
        $existing = Post::factory()->create(['slug' => 'unique-news-slug']);
        $existing->categories()->attach($this->category, ['is_primary' => true]);

        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm([
                'title' => 'Another News Story',
                'slug' => 'unique-news-slug',
                'content' => '<p>News content.</p>',
                'language' => 'hi',
                'author_id' => $this->editor->id,
                'status' => PostStatus::Draft->value,
                'categories' => [$this->category->id],
                'primary_category_id' => $this->category->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $existing->getRouteKey()])
            ->fillForm(['slug' => 'unique-news-slug'])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_manually_edited_slug_is_preserved_when_title_changes(): void
    {
        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->set('data.title', 'Initial News Headline')
            ->assertSet('data.slug', 'initial-news-headline')
            ->set('data.slug', 'editorial-custom-slug')
            ->set('data.title', 'Revised News Headline')
            ->assertSet('data.slug', 'editorial-custom-slug');
    }

    public function test_title_search_also_searches_slug(): void
    {
        $matching = Post::factory()->create(['title' => 'Ordinary title', 'slug' => 'special-search-slug']);
        $other = Post::factory()->create(['title' => 'Different title', 'slug' => 'different-slug']);

        Livewire::actingAs($this->editor)
            ->test(ListPosts::class)
            ->searchTable('special-search-slug')
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_status_author_and_language_filters_work(): void
    {
        $author = User::factory()->create();
        $matching = Post::factory()->create([
            'author_id' => $author->id,
            'status' => PostStatus::PendingReview,
            'language' => 'pa',
        ]);
        $other = Post::factory()->create([
            'status' => PostStatus::Draft,
            'language' => 'en',
        ]);

        Livewire::actingAs($this->editor)
            ->test(ListPosts::class)
            ->filterTable('status', PostStatus::PendingReview->value)
            ->filterTable('author_id', $author->id)
            ->filterTable('language', 'pa')
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_views_column_renders_formatted_values_and_sorts_descending(): void
    {
        $low = Post::factory()->create(['views_count' => 7]);
        $high = Post::factory()->create(['views_count' => 1234567]);

        Livewire::actingAs($this->editor)
            ->test(ListPosts::class)
            ->assertTableColumnStateSet('views_count', 1234567, $high)
            ->assertSee('1,234,567')
            ->sortTable('views_count', 'desc')
            ->assertCanSeeTableRecords([$high, $low], inOrder: true);
    }

    public function test_resource_query_eager_loads_authors(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($this->editor);

        $record = PostResource::getEloquentQuery()->findOrFail($post->id);

        $this->assertTrue($record->relationLoaded('author'));
        $this->assertTrue($record->relationLoaded('reviewer'));
        $this->assertSame($post->views_count, $record->views_count);
    }

    public function test_reporter_featured_media_lookup_is_scoped_to_owned_media(): void
    {
        $reporter = User::factory()->create();
        $other = User::factory()->create();
        $reporter->assignRole('reporter');
        $owned = Media::query()->create(['disk' => 'public', 'path' => 'media/owned.jpg', 'mime_type' => 'image/jpeg', 'uploaded_by' => $reporter->id]);
        $foreign = Media::query()->create(['disk' => 'public', 'path' => 'media/foreign.jpg', 'mime_type' => 'image/jpeg', 'uploaded_by' => $other->id]);

        $this->actingAs($reporter);

        $this->assertTrue(ContentAccess::scopeMedia(Media::query(), $reporter)->whereKey($owned)->exists());
        $this->assertFalse(ContentAccess::scopeMedia(Media::query(), $reporter)->whereKey($foreign)->exists());

        Livewire::actingAs($reporter)
            ->test(CreatePost::class)
            ->fillForm([
                'title' => 'Scoped media story',
                'slug' => 'scoped-media-story',
                'content' => '<p>Scoped media story.</p>',
                'language' => 'en',
                'status' => PostStatus::Draft->value,
                'featured_media_id' => $foreign->id,
                'categories' => [$this->category->id],
                'primary_category_id' => $this->category->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['featured_media_id']);

        $this->assertDatabaseMissing('posts', ['slug' => 'scoped-media-story']);
    }
}
