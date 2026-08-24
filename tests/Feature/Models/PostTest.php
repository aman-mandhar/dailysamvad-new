<?php

namespace Tests\Feature\Models;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_post_can_be_created(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create([
            'author_id' => $author->id,
            'title' => 'Rzana Punjab launches its Laravel news platform',
            'slug' => 'daily-samvad-launches-laravel-news-platform',
            'language' => 'en',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'author_id' => $author->id,
            'title' => 'Rzana Punjab launches its Laravel news platform',
            'slug' => 'daily-samvad-launches-laravel-news-platform',
            'language' => 'en',
        ]);
        $this->assertTrue($post->author->is($author));
        $this->assertFalse($post->is_breaking);
        $this->assertFalse($post->is_featured);
        $this->assertTrue($post->allow_comments);
    }

    public function test_default_status_is_draft(): void
    {
        $post = Post::query()->create([
            'title' => 'Draft news report',
            'slug' => 'draft-news-report',
            'content' => 'A complete draft news report.',
            'language' => 'en',
        ]);

        $this->assertSame(PostStatus::Draft, $post->refresh()->status);
    }

    public function test_soft_delete_works(): void
    {
        $post = Post::factory()->create();

        $post->delete();

        $this->assertSoftDeleted($post);
        $this->assertNull(Post::query()->find($post->id));
        $this->assertNotNull(Post::withTrashed()->find($post->id));
    }

    public function test_post_scopes_work(): void
    {
        $draft = Post::factory()->create();
        $olderPublished = Post::factory()->published()->create([
            'published_at' => now()->subDays(2),
        ]);
        $newerPublished = Post::factory()->published()->create([
            'published_at' => now()->subDay(),
        ]);
        $futurePublished = Post::factory()->create([
            'status' => PostStatus::Published,
            'published_at' => now()->addDay(),
        ]);
        $breaking = Post::factory()->breaking()->create();
        $featured = Post::factory()->featured()->create();

        $this->assertEqualsCanonicalizing(
            [$olderPublished->id, $newerPublished->id],
            Post::query()->published()->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$draft->id, $breaking->id, $featured->id],
            Post::query()->draft()->pluck('id')->all(),
        );
        $this->assertSame([$breaking->id], Post::query()->breaking()->pluck('id')->all());
        $this->assertSame([$featured->id], Post::query()->featured()->pluck('id')->all());
        $this->assertSame(
            [$newerPublished->id, $olderPublished->id],
            Post::query()->latestPublished()->pluck('id')->all(),
        );
        $this->assertFalse(Post::query()->published()->whereKey($futurePublished->id)->exists());
    }

    public function test_post_status_enum_contains_the_documented_values(): void
    {
        $this->assertSame(
            [
                'draft',
                'pending_review',
            'changes_requested',
            'approved',
            'scheduled',
                'published',
                'rejected',
                'archived',
            ],
            array_column(PostStatus::cases(), 'value'),
        );

        $post = Post::factory()->create(['status' => PostStatus::PendingReview]);

        $this->assertSame(PostStatus::PendingReview, $post->status);
    }

    public function test_deleting_an_author_sets_author_id_to_null(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['author_id' => $author->id]);

        $author->delete();

        $this->assertNull($post->refresh()->author_id);
        $this->assertNull($post->author);
    }
}
