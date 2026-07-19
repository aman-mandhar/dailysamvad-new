<?php

namespace App\Http\Controllers;

use App\SEO\Sitemap\SitemapManager;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SitemapController extends Controller
{
    public function index(SitemapManager $sitemaps): StreamedResponse
    {
        abort_unless($sitemaps->enabled(), 404);

        return $this->xml($sitemaps->index());
    }

    public function posts(int $page, SitemapManager $sitemaps): StreamedResponse
    {
        return ($xml = $sitemaps->posts($page)) === null ? abort(404) : $this->xml($xml);
    }

    public function categories(SitemapManager $sitemaps): StreamedResponse
    {
        return ($xml = $sitemaps->categories()) === null ? abort(404) : $this->xml($xml);
    }

    public function tags(SitemapManager $sitemaps): StreamedResponse
    {
        return ($xml = $sitemaps->tags()) === null ? abort(404) : $this->xml($xml);
    }

    public function authors(SitemapManager $sitemaps): StreamedResponse
    {
        return ($xml = $sitemaps->authors()) === null ? abort(404) : $this->xml($xml);
    }

    public function pages(SitemapManager $sitemaps): StreamedResponse
    {
        return ($xml = $sitemaps->pages()) === null ? abort(404) : $this->xml($xml);
    }

    public function news(SitemapManager $sitemaps): StreamedResponse
    {
        return ($xml = $sitemaps->news()) === null ? abort(404) : $this->xml($xml, (int) config('seo.sitemaps.news_cache_ttl', 300));
    }

    public function newsChunk(int $page, SitemapManager $sitemaps): StreamedResponse
    {
        abort_if($page < 2, 404);

        return ($xml = $sitemaps->newsPage($page)) === null ? abort(404) : $this->xml($xml, (int) config('seo.sitemaps.news_cache_ttl', 300));
    }

    public function images(SitemapManager $sitemaps): StreamedResponse
    {
        return ($xml = $sitemaps->images()) === null ? abort(404) : $this->xml($xml);
    }

    private function xml(string $content, ?int $maxAge = null): StreamedResponse
    {
        $maxAge ??= (int) config('seo.sitemaps.cache_ttl', 3600);

        return response()->stream(static fn () => print ($content), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age='.max(0, $maxAge),
        ]);
    }
}
