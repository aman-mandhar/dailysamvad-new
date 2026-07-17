<?php

namespace Tests\Feature\Frontend;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_author_archive_exposes_only_safe_public_profile_and_published_posts(): void
    {
        $author = User::factory()->create(['name' => 'Public Reporter', 'username' => 'public-reporter', 'email' => 'private@example.test', 'bio' => '<b>Public biography</b>']);
        $published = Post::factory()->published()->create(['author_id' => $author->id]);
        $draft = Post::factory()->create(['author_id' => $author->id]);

        $this->get(route('authors.show', $author->username))->assertOk()
            ->assertSee('Public Reporter')->assertSee('Public biography')->assertDontSee('<b>', false)
            ->assertDontSee('private@example.test')->assertSee($published->title)->assertDontSee($draft->title);
    }

    public function test_inactive_author_is_not_found_and_active_empty_author_is_valid(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);
        $empty = User::factory()->create(['is_active' => true]);

        $this->get(route('authors.show', $inactive->username))->assertNotFound();
        $this->get(route('authors.show', $empty->username))->assertOk()->assertSee('No published news is available from this author.');
    }
}
