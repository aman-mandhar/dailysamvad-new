<?php

namespace Tests\Feature\Frontend;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionEssentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_published_posts_and_excludes_drafts(): void
    {
        $published = Post::factory()->published()->create(['title' => 'Election result report']);
        $draft = Post::factory()->create(['title' => 'Election draft report']);

        $this->get('/search?q=Election')->assertOk()->assertSee($published->title)->assertDontSee($draft->title)->assertSee('noindex, follow');
    }

    public function test_search_pagination_preserves_query(): void
    {
        Post::factory()->count(13)->published()->create(['content' => '<p>UniqueSearchTerm report</p>']);

        $this->get('/search?q=UniqueSearchTerm&page=2')
            ->assertOk()
            ->assertViewHas('archive', fn ($archive): bool => $archive->posts->currentPage() === 2
                && $archive->posts->count() === 1
                && str_contains((string) $archive->posts->previousPageUrl(), 'q=UniqueSearchTerm'));
    }

    public function test_rss_is_valid_and_excludes_drafts(): void
    {
        $published = Post::factory()->published()->create();
        $draft = Post::factory()->create();
        $response = $this->get('/feed.xml')->assertOk()->assertHeader('content-type', 'application/rss+xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertStringContainsString($published->title, $response->getContent());
        $this->assertStringNotContainsString($draft->title, $response->getContent());
    }

    public function test_sitemap_is_valid_and_contains_public_article(): void
    {
        $post = Post::factory()->published()->create();
        $content = $this->get('/sitemap.xml')->assertOk()->assertHeader('content-type', 'application/xml; charset=UTF-8')->streamedContent();

        $this->assertNotFalse(simplexml_load_string($content));
        $this->assertStringContainsString(route('news.show', $post->slug), $content);
    }

    public function test_robots_loads_with_sitemap_and_admin_rule(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_custom_not_found_page_renders(): void
    {
        Post::factory()->published()->create(['title' => 'Suggested latest report']);

        $this->get('/definitely-missing')->assertNotFound()->assertSee('Page not found')->assertSee('Suggested latest report')->assertSee('noindex, follow');
    }
}
