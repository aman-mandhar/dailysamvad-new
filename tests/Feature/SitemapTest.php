<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['seo.sitemaps.cache_ttl' => 0, 'seo.sitemaps.news_cache_ttl' => 0]);
    }

    public function test_sitemap_index_is_valid_unique_and_references_only_enabled_children(): void
    {
        Post::factory()->published()->create();

        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('content-type', 'application/xml; charset=UTF-8');
        $xml = $this->xml($response->streamedContent());
        $locations = $this->locations($xml, 'sitemapindex');

        $this->assertSame('sitemapindex', $xml->documentElement->localName);
        $this->assertNotEmpty($locations);
        $this->assertSame($locations, array_values(array_unique($locations)));
        foreach ($locations as $location) {
            $this->assertStringStartsWith('http://localhost/', $location);
        }
        $this->assertContains(route('seo.sitemap.posts', 1), $locations);
        $this->assertContains(route('seo.sitemap.news'), $locations);
        $this->assertContains(route('seo.sitemap.images'), $locations);

        config(['seo.sitemaps.include_tags' => false]);
        $this->assertNotContains(route('seo.sitemap.tags'), $this->locations($this->xml($this->get('/sitemap.xml')->streamedContent()), 'sitemapindex'));
        $this->get('/sitemaps/tags.xml')->assertNotFound();
    }

    public function test_post_sitemaps_are_chunked_canonical_indexable_and_deterministic(): void
    {
        config(['seo.sitemaps.urls_per_sitemap' => 2]);
        $published = Post::factory()->published()->create(['slug' => 'ਪੰਜਾਬ-news', 'published_at' => now()->subHour(), 'updated_at' => now()->subMinutes(30)]);
        $canonical = Post::factory()->published()->create(['slug' => 'canonical-source', 'canonical_url' => route('home').'/canonical-story', 'published_at' => now()->subHours(2)]);
        Post::factory()->published()->create(['slug' => 'third-story', 'published_at' => now()->subHours(3)]);
        Post::factory()->create(['slug' => 'draft-story']);
        Post::factory()->scheduled()->create(['slug' => 'scheduled-story']);
        Post::factory()->published()->create(['slug' => 'noindex-story', 'seo_data' => ['robots' => ['index' => false, 'follow' => true]]]);
        $trashed = Post::factory()->published()->create(['slug' => 'trashed-story']);
        $trashed->delete();

        $first = $this->get('/sitemaps/posts-1.xml')->assertOk();
        $second = $this->get('/sitemaps/posts-2.xml')->assertOk();
        $firstLocations = $this->locations($this->xml($first->streamedContent()));
        $secondLocations = $this->locations($this->xml($second->streamedContent()));
        $all = [...$firstLocations, ...$secondLocations];

        $this->assertCount(2, $firstLocations);
        $this->assertCount(1, $secondLocations);
        $this->assertSame($all, array_values(array_unique($all)));
        $this->assertContains($published->publicUrl(), $all);
        $this->assertContains($canonical->canonical_url, $all);
        $this->assertNotContains('/news/'.$published->slug, $all);
        $this->assertStringNotContainsString('draft-story', implode(' ', $all));
        $this->assertStringNotContainsString('scheduled-story', implode(' ', $all));
        $this->assertStringNotContainsString('noindex-story', implode(' ', $all));
        $this->assertStringNotContainsString('trashed-story', implode(' ', $all));
        $this->get('/sitemaps/posts-3.xml')->assertNotFound();

        $articleHtml = $this->get($published->publicUrl())->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="'.$published->publicUrl().'">', $articleHtml);
    }

    public function test_archive_and_static_sitemaps_apply_public_content_policy(): void
    {
        $publicAuthor = User::factory()->create(['is_public' => true, 'is_active' => true]);
        $privateAuthor = User::factory()->create(['is_public' => false]);
        $category = Category::factory()->create(['is_active' => true]);
        $emptyCategory = Category::factory()->create(['is_active' => true]);
        $tag = Tag::factory()->create();
        $orphanTag = Tag::factory()->create();
        $post = Post::factory()->published()->create(['author_id' => $publicAuthor->id]);
        Post::factory()->published()->create(['author_id' => $privateAuthor->id]);
        $post->categories()->attach($category, ['is_primary' => true]);
        $post->tags()->attach($tag);

        $categories = $this->locations($this->xml($this->get('/sitemaps/categories.xml')->assertOk()->streamedContent()));
        $tags = $this->locations($this->xml($this->get('/sitemaps/tags.xml')->assertOk()->streamedContent()));
        $authorsXml = $this->get('/sitemaps/authors.xml')->assertOk()->streamedContent();
        $authors = $this->locations($this->xml($authorsXml));
        $pages = $this->locations($this->xml($this->get('/sitemaps/pages.xml')->assertOk()->streamedContent()));

        $this->assertContains(route('categories.show', $category->slug), $categories);
        $this->assertNotContains(route('categories.show', $emptyCategory->slug), $categories);
        $this->assertContains(route('tags.show', $tag->slug), $tags);
        $this->assertNotContains(route('tags.show', $orphanTag->slug), $tags);
        $this->assertContains(route('authors.show', $publicAuthor->username), $authors);
        $this->assertNotContains(route('authors.show', $privateAuthor->username), $authors);
        $this->assertStringNotContainsString($publicAuthor->email, $authorsXml);
        $this->assertSame(1, count(array_filter($pages, fn (string $url): bool => $url === route('home'))));
        $this->assertContains(route('pages.about'), $pages);
        $this->assertStringNotContainsString('/admin', implode(' ', $pages));
        $this->assertStringNotContainsString('/search', implode(' ', $pages));
        $this->assertStringNotContainsString('/login', implode(' ', $pages));
    }

    public function test_explicit_sitemap_and_robots_routes_are_not_captured_by_wildcards(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertHeader('content-type', 'application/xml; charset=UTF-8');
        $this->get('/news-sitemap.xml')->assertOk()->assertHeader('content-type', 'application/xml; charset=UTF-8');
        $this->get('/image-sitemap.xml')->assertOk()->assertHeader('content-type', 'application/xml; charset=UTF-8');
        $this->get('/robots.txt')->assertOk()->assertHeader('content-type', 'text/plain; charset=UTF-8');
        $this->assertSame('seo.sitemap.news', app('router')->getRoutes()->match(request()->create('/news-sitemap.xml'))->getName());
    }

    private function xml(string $content): DOMDocument
    {
        $document = new DOMDocument;
        $this->assertTrue($document->loadXML($content, LIBXML_NONET));

        return $document;
    }

    /** @return array<int, string> */
    private function locations(DOMDocument $document, string $root = 'urlset'): array
    {
        $this->assertSame($root, $document->documentElement->localName);

        return array_values(array_map(fn ($node): string => $node->textContent, iterator_to_array($document->getElementsByTagName('loc'))));
    }
}
