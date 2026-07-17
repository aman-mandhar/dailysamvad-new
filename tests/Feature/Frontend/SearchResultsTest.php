<?php

namespace Tests\Feature\Frontend;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_supported_fields_and_unicode_but_excludes_unpublished_posts(): void
    {
        $title = Post::factory()->published()->create(['title' => 'किसान समाचार']);
        $excerpt = Post::factory()->published()->create(['title' => 'Excerpt match', 'excerpt' => 'किसान योजना']);
        $content = Post::factory()->published()->create(['title' => 'Content match', 'content' => '<p>किसान सहायता</p>']);
        $draft = Post::factory()->create(['title' => 'किसान draft']);

        $this->get(route('search', ['q' => '  किसान  ']))->assertOk()
            ->assertSee($title->title)->assertSee($excerpt->title)->assertSee($content->title)->assertDontSee($draft->title)
            ->assertSee('noindex, follow')->assertSee('SearchResultsPage');
    }

    public function test_blank_and_wildcard_queries_do_not_return_all_posts(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Ordinary report']);
        $this->get(route('search'))->assertOk()->assertDontSee('data-archive-post="'.$post->id.'"', false)->assertSee('Enter a keyword');
        $this->get(route('search', ['q' => '%_']))->assertOk()->assertDontSee('data-archive-post="'.$post->id.'"', false);
    }

    public function test_query_is_escaped_and_pagination_preserves_normalized_query_and_canonical(): void
    {
        Post::factory()->published()->count(13)->create(['title' => 'Punjab query report']);
        $response = $this->get(route('search', ['q' => '<b>Punjab</b>', 'page' => 2]))->assertOk();

        $response->assertDontSee('<b>Punjab</b>', false)->assertSee('q=Punjab', false)->assertSee('page=2', false);
        $this->assertSame(1, substr_count($response->getContent(), '<link rel="canonical"'));
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }
}
