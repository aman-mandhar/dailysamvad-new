<?php

namespace Tests\Feature\Filament;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\PostWorkflow;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PostPublishingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->editor = $this->userWithRole('editor');
        $this->category = Category::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_reporter_cannot_publish_or_archive(): void
    {
        $reporter = $this->userWithRole('reporter');
        $pending = $this->postWithStatus(PostStatus::PendingReview);
        $published = $this->postWithStatus(PostStatus::Published, ['published_at' => now()->subHour()]);

        $this->assertFalse(Gate::forUser($reporter)->allows('publish', $pending));
        $this->assertFalse(Gate::forUser($reporter)->allows('archive', $published));
        $this->assertFalse(PostWorkflow::canTransition($reporter, PostStatus::PendingReview, PostStatus::Published));
        $this->assertFalse(PostWorkflow::canTransition($reporter, PostStatus::Published, PostStatus::Archived));
    }

    public function test_editor_can_publish_pending_review_post_and_published_at_is_populated(): void
    {
        $post = $this->postWithStatus(PostStatus::PendingReview, ['published_at' => null]);

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->set('data.status', PostStatus::Published->value)
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNotNull($post->published_at);
    }

    public function test_existing_published_at_is_preserved_when_other_fields_change(): void
    {
        $publishedAt = now()->subYear()->startOfSecond();
        $post = $this->postWithStatus(PostStatus::Published, ['published_at' => $publishedAt]);

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'title' => 'Updated Without Republishing',
                'published_at' => now(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($post->refresh()->published_at->equalTo($publishedAt));
    }

    public function test_scheduled_time_must_be_in_the_future(): void
    {
        $post = $this->postWithStatus(PostStatus::PendingReview);

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->set('data.status', PostStatus::Scheduled->value)
            ->set('data.scheduled_at', now()->subMinute()->format('Y-m-d H:i:s'))
            ->call('save')
            ->assertHasFormErrors(['scheduled_at']);

        $this->assertSame(PostStatus::PendingReview, $post->refresh()->status);
    }

    public function test_post_can_be_scheduled_for_a_future_time(): void
    {
        $post = $this->postWithStatus(PostStatus::PendingReview);
        $scheduledAt = now()->addDay()->startOfMinute();

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->set('data.status', PostStatus::Scheduled->value)
            ->set('data.scheduled_at', $scheduledAt->format('Y-m-d H:i:s'))
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();

        $this->assertSame(PostStatus::Scheduled, $post->status);
        $this->assertTrue($post->scheduled_at->equalTo($scheduledAt));
    }

    public function test_breaking_and_featured_flags_persist(): void
    {
        $post = $this->postWithStatus(PostStatus::Draft);

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->set('data.is_breaking', true)
            ->set('data.is_featured', true)
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();

        $this->assertTrue($post->is_breaking);
        $this->assertTrue($post->is_featured);
    }

    public function test_invalid_editor_transition_is_rejected(): void
    {
        $post = $this->postWithStatus(PostStatus::Draft);

        Livewire::actingAs($this->editor)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->set('data.status', PostStatus::Published->value)
            ->call('save')
            ->assertHasFormErrors(['status']);

        $this->assertSame(PostStatus::Draft, $post->refresh()->status);
    }

    public function test_super_admin_may_transition_between_any_enum_states(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->assertTrue(PostWorkflow::canTransition($superAdmin, PostStatus::Archived, PostStatus::Draft));
        $this->assertTrue(PostWorkflow::canTransition($superAdmin, PostStatus::Rejected, PostStatus::PendingReview));
    }

    public function test_editor_can_bulk_publish_and_reporter_cannot(): void
    {
        $editorPost = $this->postWithStatus(PostStatus::PendingReview);

        Livewire::actingAs($this->editor)
            ->test(ListPosts::class)
            ->callTableBulkAction('publish', [$editorPost]);

        $this->assertSame(PostStatus::Published, $editorPost->refresh()->status);
        $this->assertNotNull($editorPost->published_at);

        $reporter = $this->userWithRole('reporter');
        $reporterPost = $this->postWithStatus(PostStatus::PendingReview);

        Livewire::actingAs($reporter)
            ->test(ListPosts::class)
            ->callTableBulkAction('publish', [$reporterPost]);

        $this->assertSame(PostStatus::PendingReview, $reporterPost->refresh()->status);
    }

    public function test_editor_can_bulk_archive_and_reporter_cannot(): void
    {
        $editorPost = $this->postWithStatus(PostStatus::Published, ['published_at' => now()->subHour()]);

        Livewire::actingAs($this->editor)
            ->test(ListPosts::class)
            ->callTableBulkAction('archive', [$editorPost]);

        $this->assertSame(PostStatus::Archived, $editorPost->refresh()->status);

        $reporter = $this->userWithRole('reporter');
        $reporterPost = $this->postWithStatus(PostStatus::Published, ['published_at' => now()->subHour()]);

        Livewire::actingAs($reporter)
            ->test(ListPosts::class)
            ->callTableBulkAction('archive', [$reporterPost]);

        $this->assertSame(PostStatus::Published, $reporterPost->refresh()->status);
    }

    public function test_stale_transition_revalidates_the_locked_database_status(): void
    {
        $post = $this->postWithStatus(PostStatus::PendingReview);
        $stale = Post::query()->findOrFail($post->id);
        $post->update(['status' => PostStatus::Draft]);

        try {
            PostWorkflow::transition($this->editor, $stale, PostStatus::Published);
            $this->fail('A stale transition should not overwrite a newer workflow status.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame(PostStatus::Draft, $post->refresh()->status);
    }

    /** @param array<string, mixed> $attributes */
    private function postWithStatus(PostStatus $status, array $attributes = []): Post
    {
        $post = Post::factory()->create([
            'status' => $status,
            ...$attributes,
        ]);
        $post->categories()->attach($this->category, ['is_primary' => true]);

        return $post;
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
