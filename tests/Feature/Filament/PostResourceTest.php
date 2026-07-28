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
use Filament\Forms\Components\RichEditor;
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
        $ownPost = Post::factory()->create(['author_id' => $this->editor]);
        $otherPost = Post::factory()->create(['reviewed_by' => User::factory()->create()]);
        $reviewer = User::factory()->create();
        $reviewer->assignRole('reviewer');

        $this->assertTrue(Gate::forUser($this->editor)->allows('viewAny', Post::class));
        $this->assertTrue(Gate::forUser($this->editor)->allows('view', $otherPost));
        $this->assertTrue(Gate::forUser($this->editor)->allows('create', Post::class));
        $this->assertTrue(Gate::forUser($this->editor)->allows('update', $ownPost));
        $this->assertFalse(Gate::forUser($this->editor)->allows('update', $otherPost));
        $this->editor->givePermissionTo('update all posts');
        $this->assertFalse(Gate::forUser($this->editor)->allows('update', $otherPost));
        $this->assertFalse(Gate::forUser($this->editor)->allows('delete', $otherPost));
        $this->assertFalse(Gate::forUser($reviewer)->allows('view', $otherPost));
        $this->assertFalse(Gate::forUser($reviewer)->allows('create', Post::class));
        $this->assertFalse(Gate::forUser($reviewer)->allows('update', $otherPost));
    }

    public function test_authorized_user_can_open_post_resource_pages(): void
    {
        $post = Post::factory()->create(['author_id' => $this->editor]);

        $this->actingAs($this->editor)->get(PostResource::getUrl('index'))->assertOk();
        $this->actingAs($this->editor)->get(PostResource::getUrl('create'))->assertOk();
        $this->actingAs($this->editor)
            ->get(PostResource::getUrl('edit', ['record' => $post]))
            ->assertOk();
    }

    public function test_post_editor_has_newsroom_media_embed_alignment_and_color_tools(): void
    {
        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->assertFormFieldExists('content', function (RichEditor $field): bool {
                $buttons = collect($field->getToolbarButtons())
                    ->flatten()
                    ->filter(fn (mixed $button): bool => is_string($button))
                    ->all();
                $blockIds = collect($field->getCustomBlocks())
                    ->flatten()
                    ->map(fn (string $block): string => $block::getId())
                    ->all();

                return collect(['alignJustify', 'textColor', 'highlight', 'customBlocks'])->every(fn (string $button): bool => in_array($button, $buttons, true))
                    && collect(['media-image', 'youtube-video', 'google-drive-pdf', 'x-post'])->every(fn (string $block): bool => in_array($block, $blockIds, true));
            });
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
                'status' => PostStatus::Draft->value,
                'categories' => [$this->category->id],
                'primary_category_id' => $this->category->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(PostResource::getUrl('create'));

        $post = Post::query()->where('slug', 'punjab-assembly-education-plan')->firstOrFail();

        $this->assertTrue($post->author->is($this->editor));
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertSame('pa', $post->language);

        Livewire::actingAs($this->editor)
            ->test(ListPosts::class)
            ->assertCanSeeTableRecords([$post]);
    }

    public function test_author_is_display_only_and_cannot_be_changed_by_tampering(): void
    {
        $other = User::factory()->create();

        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->assertFormFieldDisabled('author_display')
            ->fillForm([
                'title' => 'Read-only author story',
                'slug' => 'read-only-author-story',
                'content' => '<p>Author cannot be changed.</p>',
                'language' => 'en',
                'status' => PostStatus::Draft->value,
                'author_id' => $other->id,
                'categories' => [$this->category->id],
                'primary_category_id' => $this->category->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'read-only-author-story')->firstOrFail();
        $this->assertSame($this->editor->id, $post->author_id);

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertFormFieldDisabled('author_display')
            ->set('data.author_id', $other->id)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($this->editor->id, $post->refresh()->author_id);
    }

    public function test_post_creation_success_dialog_is_shown_to_editor_with_record_actions(): void
    {
        $post = Post::factory()->create([
            'author_id' => $this->editor->id,
            'slug' => 'dialog-post',
            'published_at' => now(),
        ]);

        session()->flash('filament.posts.created_post_id', $post->id);

        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->assertActionMounted('postCreated')
            ->assertMountedActionModalSee([
                'Post Created Successfully',
                'The post has been saved. Choose what you would like to do next.',
                'View Post',
                'All Posts',
                'Create Another',
            ])
            ->assertSee($post->publicUrl())
            ->assertSee(PostResource::getUrl('index'))
            ->assertSee(PostResource::getUrl('create'));
    }

    public function test_post_creation_success_dialog_is_limited_to_super_admin_admin_and_editor(): void
    {
        foreach (['super-admin', 'admin', 'editor'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $post = Post::factory()->create(['author_id' => $user->id]);

            session()->flash('filament.posts.created_post_id', $post->id);

            Livewire::actingAs($user)
                ->test(CreatePost::class)
                ->assertActionMounted('postCreated');
        }

        $reporter = User::factory()->create();
        $reporter->assignRole('reporter');
        $post = Post::factory()->create(['author_id' => $reporter->id]);

        session()->flash('filament.posts.created_post_id', $post->id);

        Livewire::actingAs($reporter)
            ->test(CreatePost::class)
            ->assertActionNotMounted('postCreated');
    }

    public function test_reporter_is_redirected_to_post_list_after_successful_creation(): void
    {
        $reporter = User::factory()->create();
        $reporter->assignRole('reporter');

        Livewire::actingAs($reporter)
            ->test(CreatePost::class)
            ->fillForm([
                'title' => 'Reporter post',
                'slug' => 'reporter-post',
                'content' => '<p>Reporter post content.</p>',
                'language' => 'en',
                'status' => PostStatus::Draft->value,
                'categories' => [$this->category->id],
                'primary_category_id' => $this->category->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertActionNotMounted('postCreated')
            ->assertRedirect(PostResource::getUrl('index'));

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_editor_can_publish_a_post_directly_during_creation(): void
    {
        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm([
                'title' => 'Directly Published Story',
                'slug' => 'directly-published-story',
                'content' => '<p>This story is ready for immediate publication.</p>',
                'language' => 'en',
                'author_id' => $this->editor->id,
                'status' => PostStatus::Published->value,
                'categories' => [$this->category->id],
                'primary_category_id' => $this->category->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'directly-published-story')->firstOrFail();

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertSame($this->editor->id, $post->published_by);
        $this->assertDatabaseHas('post_workflow_events', [
            'post_id' => $post->id,
            'actor_id' => $this->editor->id,
            'event' => 'published',
            'to_status' => PostStatus::Published->value,
        ]);
    }

    public function test_create_status_defaults_to_published_for_editorial_roles_and_draft_for_reporter(): void
    {
        foreach (['super-admin', 'admin', 'editor'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            Livewire::actingAs($user)
                ->test(CreatePost::class)
                ->assertSet('data.status', PostStatus::Published->value);
        }

        $reporter = User::factory()->create();
        $reporter->assignRole('reporter');

        Livewire::actingAs($reporter)
            ->test(CreatePost::class)
            ->assertSet('data.status', PostStatus::Draft->value);
    }

    public function test_post_can_be_edited(): void
    {
        $post = Post::factory()->create([
            'author_id' => $this->editor,
            'status' => PostStatus::Draft,
            'language' => 'hi',
        ]);
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
        $this->assertSame(PostStatus::PendingReview, $post->status);
        $this->assertSame('en', $post->language);
    }

    public function test_super_admin_admin_and_editor_can_change_status_on_edit(): void
    {
        foreach (['super-admin', 'admin', 'editor'] as $role) {
            $actor = User::factory()->create();
            $actor->assignRole($role);
            $post = Post::factory()->create([
                'author_id' => $role === 'editor' ? $actor->id : $this->editor->id,
                'status' => PostStatus::Draft,
            ]);
            $post->categories()->attach($this->category, ['is_primary' => true]);

            Livewire::actingAs($actor)
                ->test(EditPost::class, ['record' => $post->getRouteKey()])
                ->fillForm(['status' => PostStatus::Published->value])
                ->call('save')
                ->assertHasNoFormErrors();

            $post->refresh();
            $this->assertSame(PostStatus::Published, $post->status, $role);
            $this->assertNotNull($post->published_at, $role);
            $this->assertDatabaseHas('post_workflow_events', [
                'post_id' => $post->id,
                'actor_id' => $actor->id,
                'event' => 'status_changed',
                'from_status' => PostStatus::Draft->value,
                'to_status' => PostStatus::Published->value,
            ]);
        }
    }

    public function test_reporter_cannot_change_status_by_tampering_with_edit_form(): void
    {
        $reporter = User::factory()->create();
        $reporter->assignRole('reporter');
        $post = Post::factory()->create(['author_id' => $reporter->id, 'status' => PostStatus::Draft]);
        $post->categories()->attach($this->category, ['is_primary' => true]);

        Livewire::actingAs($reporter)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->set('data.status', PostStatus::Published->value)
            ->call('save')
            ->assertHasFormErrors(['status']);

        $this->assertSame(PostStatus::Draft, $post->refresh()->status);
        $this->assertDatabaseMissing('post_workflow_events', [
            'post_id' => $post->id,
            'to_status' => PostStatus::Published->value,
        ]);
    }

    public function test_slug_must_be_unique_and_ignores_the_current_post(): void
    {
        $existing = Post::factory()->create([
            'author_id' => $this->editor,
            'slug' => 'unique-news-slug',
        ]);
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
            ->assertHasFormErrors(['slug' => 'unique'])
            ->assertActionNotMounted('postCreated');

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

    public function test_posts_are_sorted_by_newest_creation_date_by_default(): void
    {
        $newest = Post::factory()->create(['created_at' => now()]);
        $oldest = Post::factory()->create(['created_at' => now()->subDay()]);

        Livewire::actingAs($this->editor)
            ->test(ListPosts::class)
            ->assertCanSeeTableRecords([$newest, $oldest], inOrder: true);
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
