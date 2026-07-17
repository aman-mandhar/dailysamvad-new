<?php

namespace Tests\Feature\Frontend;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_filters_published_posts_and_renders_archive_metadata(): void
    {
        $tag = Tag::factory()->create(['name' => 'Politics']);
        $published = Post::factory()->published()->create(['title' => 'Tagged public report']);
        $draft = Post::factory()->create(['title' => 'Tagged draft']);
        $tag->posts()->attach([$published->id, $draft->id]);

        $response = $this->get(route('tags.show', $tag->slug))->assertOk()
            ->assertSee($published->title)->assertDontSee($draft->title)
            ->assertSee('index, follow')->assertSee('CollectionPage');

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_valid_empty_tag_returns_200_and_invalid_tag_returns_404(): void
    {
        $tag = Tag::factory()->create();
        $this->get(route('tags.show', $tag->slug))->assertOk()->assertSee('No published news is available for this tag.');
        $this->get('/tag/not-real')->assertNotFound();
    }
}
