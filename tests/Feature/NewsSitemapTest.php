<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Carbon\CarbonImmutable;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_sitemap_applies_rolling_48_hour_boundary_and_news_fields(): void
    {
        CarbonImmutable::setTestNow('2026-07-19 12:00:00');
        config(['seo.sitemaps.news_cache_ttl' => 0, 'seo.news.publication_name' => 'Daily Samvad News']);
        $author = User::factory()->create();
        $inside = Post::factory()->published()->create(['author_id' => $author->id, 'title' => '<b>ਪੰਜਾਬ & हिंदी</b>', 'language' => 'pa-IN', 'published_at' => now()->subHours(47)->subMinutes(59)]);
        $boundary = Post::factory()->published()->create(['author_id' => $author->id, 'published_at' => now()->subHours(48)]);
        $outside = Post::factory()->published()->create(['author_id' => $author->id, 'published_at' => now()->subHours(48)->subMinute(), 'updated_at' => now()]);
        Post::factory()->create(['author_id' => $author->id, 'published_at' => now()->subHour()]);
        Post::factory()->scheduled()->create(['author_id' => $author->id, 'scheduled_at' => now()->addMinute()]);

        $content = $this->get('/news-sitemap.xml')->assertOk()->streamedContent();
        $document = new DOMDocument;
        $this->assertTrue($document->loadXML($content, LIBXML_NONET));
        $locations = array_map(fn ($node): string => $node->textContent, iterator_to_array($document->getElementsByTagName('loc')));

        $this->assertContains($inside->publicUrl(), $locations);
        $this->assertContains($boundary->publicUrl(), $locations);
        $this->assertNotContains($outside->publicUrl(), $locations);
        $this->assertSame('Daily Samvad News', $document->getElementsByTagNameNS('http://www.google.com/schemas/sitemap-news/0.9', 'name')->item(0)->textContent);
        $this->assertSame('pa', $document->getElementsByTagNameNS('http://www.google.com/schemas/sitemap-news/0.9', 'language')->item(0)->textContent);
        $this->assertSame('ਪੰਜਾਬ & हिंदी', $document->getElementsByTagNameNS('http://www.google.com/schemas/sitemap-news/0.9', 'title')->item(0)->textContent);
        $this->assertNotFalse(date_create($document->getElementsByTagNameNS('http://www.google.com/schemas/sitemap-news/0.9', 'publication_date')->item(0)->textContent));

        $standard = $this->get('/sitemaps/posts-1.xml')->streamedContent();
        $this->assertStringContainsString($outside->publicUrl(), $standard);
    }

    public function test_news_sitemap_limit_is_never_more_than_one_thousand_and_empty_xml_is_valid(): void
    {
        config(['seo.sitemaps.news_cache_ttl' => 0, 'seo.news.limit' => 1500]);
        $author = User::factory()->create();
        Post::withoutEvents(fn () => Post::factory()->count(1002)->published()->create(['author_id' => $author->id, 'published_at' => now()->subHour()]));

        $document = new DOMDocument;
        $this->assertTrue($document->loadXML($this->get('/news-sitemap.xml')->assertOk()->streamedContent(), LIBXML_NONET));
        $this->assertCount(1000, $document->getElementsByTagNameNS('http://www.google.com/schemas/sitemap-news/0.9', 'news'));
        $this->assertTrue($document->loadXML($this->get('/news-sitemaps/news-2.xml')->assertOk()->streamedContent(), LIBXML_NONET));
        $this->assertCount(2, $document->getElementsByTagNameNS('http://www.google.com/schemas/sitemap-news/0.9', 'news'));
        $this->assertStringContainsString(route('seo.sitemap.news.chunk', 2), $this->get('/sitemap.xml')->streamedContent());

        Post::query()->delete();
        $this->assertTrue($document->loadXML($this->get('/news-sitemap.xml')->streamedContent(), LIBXML_NONET));
        $this->assertCount(0, $document->getElementsByTagNameNS('http://www.google.com/schemas/sitemap-news/0.9', 'news'));
    }
}
