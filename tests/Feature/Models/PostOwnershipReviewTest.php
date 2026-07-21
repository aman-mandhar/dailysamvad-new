<?php

namespace Tests\Feature\Models;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PostOwnershipReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_belongs_to_an_author_and_reviewer(): void
    {
        $author = User::factory()->create();
        $reviewer = User::factory()->create();
        $post = Post::factory()->create([
            'author_id' => $author,
            'reviewed_by' => $reviewer,
            'submitted_at' => now()->subHour(),
            'reviewed_at' => now(),
            'review_notes' => 'Checked against the source.',
        ]);

        $this->assertTrue($post->author->is($author));
        $this->assertTrue($post->reviewer->is($reviewer));
        $this->assertTrue($author->authoredPosts->contains($post));
        $this->assertTrue($reviewer->reviewedPosts->contains($post));
        $this->assertInstanceOf(Carbon::class, $post->submitted_at);
        $this->assertInstanceOf(Carbon::class, $post->reviewed_at);
    }

    public function test_deleting_author_and_reviewer_nulls_ownership_without_deleting_post(): void
    {
        $author = User::factory()->create();
        $reviewer = User::factory()->create();
        $post = Post::factory()->create(['author_id' => $author, 'reviewed_by' => $reviewer]);

        $author->delete();
        $reviewer->delete();

        $this->assertDatabaseHas('posts', [
            'id' => $post->getKey(),
            'author_id' => null,
            'reviewed_by' => null,
        ]);
    }

    public function test_existing_view_count_values_are_preserved(): void
    {
        $post = Post::factory()->create(['views_count' => 173]);

        $this->assertSame(173, $post->fresh()->views_count);
        $this->assertSame(0, Post::factory()->create()->views_count);
    }
}
