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
        $index = $this->get('/sitemap.xml')->assertOk()->assertHeader('content-type', 'application/xml; charset=UTF-8')->streamedContent();
        $content = $this->get('/sitemaps/posts-1.xml')->assertOk()->streamedContent();

        $this->assertNotFalse(simplexml_load_string($index));
        $this->assertNotFalse(simplexml_load_string($content));
        $this->assertStringContainsString(route('seo.sitemap.posts', 1), $index);
        $this->assertStringContainsString($post->publicUrl(), $content);
    }

    public function test_robots_loads_with_sitemap_and_admin_rule(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_browser_not_found_response_redirects_home(): void
    {
        $this->get('/definitely-missing')->assertRedirect(route('home'));
    }

    public function test_json_not_found_response_remains_a_404(): void
    {
        $this->getJson('/definitely-missing')->assertNotFound();
    }
}
