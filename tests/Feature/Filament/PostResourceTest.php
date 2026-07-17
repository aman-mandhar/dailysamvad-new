<?php

namespace Tests\Feature\Filament;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Models\User;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->editor = User::factory()->create();
        $this->editor->assignRole('editor');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_post_policy_uses_post_permissions(): void
    {
        $post = Post::factory()->create();
        $reviewer = User::factory()->create();
        $reviewer->assignRole('reviewer');

        $this->assertTrue(Gate::forUser($this->editor)->allows('viewAny', Post::class));
        $this->assertTrue(Gate::forUser($this->editor)->allows('view', $post));
        $this->assertTrue(Gate::forUser($this->editor)->allows('create', Post::class));
        $this->assertTrue(Gate::forUser($this->editor)->allows('update', $post));
        $this->assertTrue(Gate::forUser($this->editor)->allows('delete', $post));
        $this->assertTrue(Gate::forUser($reviewer)->allows('view', $post));
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
        $author = User::factory()->create();

        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm([
                'title' => 'Punjab Assembly Reviews New Education Plan',
                'slug' => 'punjab-assembly-education-plan',
                'excerpt' => 'The assembly reviewed a proposed education plan.',
                'content' => '<p>Members discussed the proposed education plan during the session.</p>',
                'language' => 'pa',
                'author_id' => $author->id,
                'status' => PostStatus::PendingReview->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'punjab-assembly-education-plan')->firstOrFail();

        $this->assertTrue($post->author->is($author));
        $this->assertSame(PostStatus::PendingReview, $post->status);
        $this->assertSame('pa', $post->language);
    }

    public function test_post_can_be_edited(): void
    {
        $post = Post::factory()->create(['status' => PostStatus::Draft, 'language' => 'hi']);

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

    public function test_slug_must_be_unique_and_ignores_the_current_post(): void
    {
        $existing = Post::factory()->create(['slug' => 'unique-news-slug']);

        Livewire::actingAs($this->editor)
            ->test(CreatePost::class)
            ->fillForm([
                'title' => 'Another News Story',
                'slug' => 'unique-news-slug',
                'content' => '<p>News content.</p>',
                'language' => 'hi',
                'author_id' => $this->editor->id,
                'status' => PostStatus::Draft->value,
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

    public function test_resource_query_eager_loads_authors(): void
    {
        $post = Post::factory()->create();

        $record = PostResource::getEloquentQuery()->findOrFail($post->id);

        $this->assertTrue($record->relationLoaded('author'));
    }
}
