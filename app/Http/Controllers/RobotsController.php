<?php

namespace App\Http\Controllers;

use App\SEO\Sitemap\SitemapManager;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(SitemapManager $sitemaps): Response
    {
        return response($sitemaps->robots(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age='.(int) config('seo.sitemaps.robots_cache_ttl', 300),
        ]);
    }
}
