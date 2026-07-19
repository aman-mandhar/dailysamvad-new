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

    public function test_hidden_and_empty_authors_are_not_public_but_inactive_authors_keep_historical_attribution(): void
    {
        $hidden = User::factory()->create(['is_public' => false]);
        $inactive = User::factory()->create(['is_active' => false, 'is_public' => true]);
        $empty = User::factory()->create(['is_public' => true]);
        Post::factory()->published()->create(['author_id' => $hidden->id]);
        Post::factory()->published()->create(['author_id' => $inactive->id]);

        $this->get(route('authors.show', $hidden->username))->assertNotFound();
        $this->get(route('authors.show', $inactive->username))->assertOk();
        $this->get(route('authors.show', $empty->username))->assertNotFound();
    }

    public function test_author_designation_avatar_and_unicode_name_render_safely(): void
    {
        $author = User::factory()->create([
            'name' => 'à¨—à©à¨°à¨ªà©à¨°à©€à¨¤ à¨•à©Œà¨°',
            'designation' => 'Senior Reporter',
            'avatar_path' => null,
            'bio' => '<script>alert("private")</script>Public profile',
        ]);
        Post::factory()->published()->create(['author_id' => $author->id]);

        $this->get(route('authors.show', $author->username))
            ->assertOk()
            ->assertSee('à¨—à©à¨°à¨ªà©à¨°à©€à¨¤ à¨•à©Œà¨°')
            ->assertSee('Senior Reporter')
            ->assertDontSee('<script>', false)
            ->assertDontSee('ds-archive-author-avatar', false);
    }
}
