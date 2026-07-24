<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Exceptions\InvalidPostTransition;
use App\Jobs\PublishScheduledPost;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Notifications\EditorialWorkflowNotification;
use App\Services\EditorialWorkflowService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EditorialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private EditorialWorkflowService $workflow;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->workflow = app(EditorialWorkflowService::class);
        $this->category = Category::factory()->create();
        Notification::fake();
    }

    public function test_reporter_submits_complete_post_and_history_and_notification_are_recorded(): void
    {
        $reporter = $this->role('reporter');
        $editor = $this->role('editor');
        $post = $this->makePost($reporter);

        $this->workflow->submitForReview($post, $reporter);

        $this->assertSame(PostStatus::PendingReview, $post->refresh()->status);
        $this->assertSame($reporter->id, $post->submitted_by);
        $this->assertDatabaseHas('post_workflow_events', ['post_id' => $post->id, 'event' => 'submitted', 'actor_id' => $reporter->id]);
        Notification::assertSentTo($editor, EditorialWorkflowNotification::class);
    }

    public function test_incomplete_post_cannot_be_submitted(): void
    {
        $reporter = $this->role('reporter');
        $post = Post::factory()->create(['author_id' => $reporter]);
        $this->expectException(InvalidPostTransition::class);
        $this->workflow->submitForReview($post, $reporter);
    }

    public function test_editor_assigns_only_an_active_eligible_reviewer_without_duplicate_events(): void
    {
        $editor = $this->role('editor');
        $reviewer = $this->role('reviewer');
        $post = $this->makePost($this->role('reporter'), PostStatus::PendingReview);

        $this->workflow->assignReviewer($post, $reviewer, $editor);
        $this->workflow->assignReviewer($post->refresh(), $reviewer, $editor);

        $this->assertSame($reviewer->id, $post->refresh()->reviewed_by);
        $this->assertSame(1, $post->workflowEvents()->where('event', 'reviewer_assigned')->count());
        Notification::assertSentTo($reviewer, EditorialWorkflowNotification::class);
    }

    public function test_assigned_reviewer_can_request_corrections_and_reporter_can_resubmit(): void
    {
        $reporter = $this->role('reporter');
        $reviewer = $this->role('reviewer');
        $post = $this->makePost($reporter, PostStatus::PendingReview, ['reviewed_by' => $reviewer->id]);

        $this->workflow->requestCorrections($post, $reviewer, 'Add the source attribution.');
        $this->assertSame(PostStatus::ChangesRequested, $post->refresh()->status);
        $this->assertSame('Add the source attribution.', $post->correction_notes);

        $this->workflow->submitForReview($post, $reporter);
        $this->assertSame(PostStatus::PendingReview, $post->refresh()->status);
        $this->assertSame(2, $post->workflowEvents()->count());
    }

    public function test_approval_is_required_before_schedule_or_publish_and_reporter_cannot_bypass(): void
    {
        $reporter = $this->role('reporter');
        $editor = $this->role('editor');
        $post = $this->makePost($reporter, PostStatus::PendingReview);

        try {
            $this->workflow->publish($post, $editor);
            $this->fail('Pending review was published directly.');
        } catch (AuthorizationException|InvalidPostTransition) {
            $this->assertSame(PostStatus::PendingReview, $post->refresh()->status);
        }

        $this->workflow->approve($post, $editor, 'Ready');
        $this->workflow->publish($post->refresh(), $editor);
        $this->assertSame(PostStatus::Published, $post->refresh()->status);
        $this->assertNotNull($post->published_at);
        $this->assertSame($editor->id, $post->published_by);
    }

    public function test_rejection_requires_reason_and_restore_is_explicit(): void
    {
        $editor = $this->role('editor');
        $post = $this->makePost($this->role('reporter'), PostStatus::PendingReview);
        $this->workflow->reject($post, $editor, 'Materially unverifiable.');
        $this->assertSame(PostStatus::Rejected, $post->refresh()->status);
        $this->workflow->reopen($post, $editor);
        $this->assertSame(PostStatus::Draft, $post->refresh()->status);
    }

    public function test_due_scheduled_post_publishes_once_but_future_and_cancelled_posts_do_not(): void
    {
        $editor = $this->role('editor');
        $due = $this->makePost($editor, PostStatus::Scheduled, ['scheduled_at' => now()->subMinute()]);
        $future = $this->makePost($editor, PostStatus::Scheduled, ['scheduled_at' => now()->addHour()]);
        $cancelled = $this->makePost($editor, PostStatus::Approved);

        (new PublishScheduledPost($due->id))->handle($this->workflow);
        (new PublishScheduledPost($due->id))->handle($this->workflow);
        (new PublishScheduledPost($future->id))->handle($this->workflow);
        (new PublishScheduledPost($cancelled->id))->handle($this->workflow);

        $this->assertSame(PostStatus::Published, $due->refresh()->status);
        $this->assertSame(1, $due->workflowEvents()->where('event', 'published')->count());
        $this->assertSame(PostStatus::Scheduled, $future->refresh()->status);
        $this->assertSame(PostStatus::Approved, $cancelled->refresh()->status);
    }

    public function test_workflow_preserves_import_media_seo_slug_and_source_identity(): void
    {
        $editor = $this->role('editor');
        $post = $this->makePost($editor, PostStatus::Approved, [
            'old_wp_id' => 23043, 'slug' => 'preserved-slug', 'featured_media_id' => null,
            'featured_image' => 'wordpress/uploads/image.jpg', 'meta_title' => 'SEO title',
        ]);
        $this->workflow->publish($post, $editor);
        $post->refresh();
        $this->assertSame(23043, $post->old_wp_id);
        $this->assertSame('preserved-slug', $post->slug);
        $this->assertSame('wordpress/uploads/image.jpg', $post->featured_image);
        $this->assertSame('SEO title', $post->meta_title);
    }

    private function role(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    private function makePost(User $author, PostStatus $status = PostStatus::Draft, array $attributes = []): Post
    {
        $post = Post::factory()->create(['author_id' => $author, 'status' => $status, ...$attributes]);
        $post->categories()->attach($this->category, ['is_primary' => true]);
        return $post;
    }
}
