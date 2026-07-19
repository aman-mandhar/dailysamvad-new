<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relationships_include_only_currently_published_posts(): void
    {
        $author = User::factory()->create();
        $published = Post::factory()->published()->create(['author_id' => $author->id]);
        Post::factory()->create(['author_id' => $author->id]);
        Post::factory()->create([
            'author_id' => $author->id,
            'status' => PostStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $this->assertCount(3, $author->posts);
        $this->assertSame([$published->id], $author->publishedPosts()->pluck('id')->all());
        $this->assertTrue($published->author->is($author));
    }

    public function test_public_author_scope_requires_visibility_and_an_eligible_post(): void
    {
        $public = User::factory()->create();
        $hidden = User::factory()->create(['is_public' => false]);
        $empty = User::factory()->create();
        Post::factory()->published()->create(['author_id' => $public->id]);
        Post::factory()->published()->create(['author_id' => $hidden->id]);

        $this->assertSame([$public->id], User::query()->publicAuthor()->pluck('id')->all());
        $this->assertNotContains($empty->id, User::query()->publicAuthor()->pluck('id')->all());
    }
}
