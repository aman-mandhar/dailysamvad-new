<?php

namespace Tests\Feature\Frontend;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EpaperPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_post_has_an_epaper_link_and_dedicated_reading_page(): void
    {
        $post = Post::factory()->published()->create();

        $this->get($post->publicUrl())
            ->assertOk()
            ->assertSee(route('epaper.show', $post->slug), false)
            ->assertSee('View in ePaper');

        $this->get(route('epaper.show', $post->slug))
            ->assertOk()
            ->assertViewIs('epaper.show')
            ->assertSee($post->title)
            ->assertSee('images/epaper-header.png', false)
            ->assertSee('Print')
            ->assertSee('Download JPEG')
            ->assertSee('Share JPEG')
            ->assertDontSee('Monthly edition', false);
    }

    public function test_epaper_places_featured_image_between_title_and_sanitized_content(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('epaper/story.jpg', 'image');
        $post = Post::factory()->published()->create([
            'title' => 'ePaper headline position',
            'featured_image' => 'epaper/story.jpg',
            'content' => '<p onclick="alert(1)">First newspaper paragraph</p><script>alert(2)</script>',
        ]);

        $response = $this->get(route('epaper.show', $post->slug))->assertOk();

        $response->assertSeeInOrder([
            'ePaper headline position',
            '/storage/epaper/story.jpg',
            'First newspaper paragraph',
        ], false)
            ->assertDontSee('onclick=', false)
            ->assertDontSee('<script>alert(2)</script>', false)
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertSee('<link rel="canonical" href="'.$post->publicUrl().'">', false);
    }

    public function test_unpublished_posts_are_redirected_without_exposing_an_epaper(): void
    {
        $post = Post::factory()->create();

        $this->get(route('epaper.show', $post->slug))
            ->assertRedirect(route('home'))
            ->assertDontSee($post->title);
    }
}
