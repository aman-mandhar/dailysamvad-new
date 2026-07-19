<?php

namespace Tests\Feature;

use App\SEO\Sitemap\SitemapCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RobotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_production_robots_blocks_indexing_and_references_absolute_sitemaps(): void
    {
        config(['seo.robots.allow_indexing' => false, 'seo.sitemaps.robots_cache_ttl' => 3600]);

        $content = $this->get('/robots.txt')->assertOk()->assertHeader('content-type', 'text/plain; charset=UTF-8')->getContent();

        $this->assertStringContainsString("User-agent: *\nDisallow: /", $content);
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $content);
        $this->assertStringContainsString('Sitemap: '.route('seo.sitemap.news'), $content);
        $this->assertSame(1, substr_count($content, 'Sitemap: '.route('sitemap')."\n"));
        $this->assertStringNotContainsString('Noindex:', $content);
        $this->assertStringNotContainsString('INDEXNOW', $content);
    }

    public function test_production_policy_allows_public_assets_and_disallows_only_configured_sensitive_prefixes(): void
    {
        config(['seo.robots.allow_indexing' => true, 'seo.robots.disallow' => ['/admin']]);
        app(SitemapCache::class)->invalidate();

        $content = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString("Allow: /\n", $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringNotContainsString('Disallow: /build', $content);
        $this->assertStringNotContainsString('Disallow: /storage', $content);
        $this->assertStringNotContainsString('Disallow: /livewire', $content);
    }
}
