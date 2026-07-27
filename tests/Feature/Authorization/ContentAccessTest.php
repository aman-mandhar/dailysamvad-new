<?php

namespace Tests\Feature\Authorization;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use App\Support\Authorization\ContentAccess;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_view_all_posts_includes_every_status_null_publication_dates_and_other_authors(): void
    {
        $admin = $this->userWithRole('admin');
        $otherAuthor = $this->userWithRole('reporter');

        $visible = collect(PostStatus::cases())->map(fn (PostStatus $status): Post => Post::factory()->create([
            'old_wp_id' => $status === PostStatus::Published ? 12345 : null,
            'author_id' => $otherAuthor->id,
            'status' => $status,
            'published_at' => $status === PostStatus::Published ? now()->subMinute() : null,
        ]));

        $newDraft = Post::factory()->create([
            'author_id' => $admin->id,
            'status' => PostStatus::Draft,
            'published_at' => null,
        ]);
        $newPublished = Post::factory()->published()->create(['author_id' => $admin->id]);
        $deleted = Post::factory()->create(['author_id' => $otherAuthor->id]);
        $deleted->delete();

        $scopedIds = ContentAccess::scopePosts(Post::query(), $admin)->pluck('id');

        $this->assertEqualsCanonicalizing(
            $visible->push($newDraft, $newPublished)->pluck('id')->all(),
            $scopedIds->all(),
        );
        $this->assertFalse($scopedIds->contains($deleted->id));
    }

    public function test_reporter_sees_own_work_but_not_another_reporters_posts(): void
    {
        $reporter = $this->userWithRole('reporter');
        $otherReporter = $this->userWithRole('reporter');
        $ownDraft = Post::factory()->create(['author_id' => $reporter->id, 'status' => PostStatus::Draft]);
        $ownSubmitted = Post::factory()->create(['author_id' => $reporter->id, 'status' => PostStatus::PendingReview]);
        $foreignDraft = Post::factory()->create(['author_id' => $otherReporter->id, 'status' => PostStatus::Draft]);
        $foreignUnassigned = Post::factory()->create(['author_id' => $otherReporter->id, 'status' => PostStatus::PendingReview]);

        $scopedIds = ContentAccess::scopePosts(Post::query(), $reporter)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$ownDraft->id, $ownSubmitted->id], $scopedIds);
        $this->assertNotContains($foreignDraft->id, $scopedIds);
        $this->assertNotContains($foreignUnassigned->id, $scopedIds);
    }

    public function test_reviewer_sees_only_posts_assigned_to_them(): void
    {
        $reviewer = $this->userWithRole('reviewer');
        $otherReviewer = $this->userWithRole('reviewer');
        $assigned = Post::factory()->create([
            'reviewed_by' => $reviewer->id,
            'status' => PostStatus::PendingReview,
        ]);
        $assignedElsewhere = Post::factory()->create([
            'reviewed_by' => $otherReviewer->id,
            'status' => PostStatus::PendingReview,
        ]);
        $unassigned = Post::factory()->create(['status' => PostStatus::PendingReview]);

        $scopedIds = ContentAccess::scopePosts(Post::query(), $reviewer)->pluck('id')->all();

        $this->assertSame([$assigned->id], $scopedIds);
        $this->assertNotContains($assignedElsewhere->id, $scopedIds);
        $this->assertNotContains($unassigned->id, $scopedIds);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
