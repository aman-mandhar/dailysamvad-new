<?php

namespace App\SEO\Sitemap\Commands;

use App\SEO\Sitemap\SitemapManager;
use Illuminate\Console\Command;

class WarmSitemapsCommand extends Command
{
    protected $signature = 'seo:sitemaps:warm';

    protected $description = 'Warm configured sitemap caches without remote requests';

    public function handle(SitemapManager $sitemaps): int
    {
        $sitemaps->index();
        for ($page = 1; $page <= $sitemaps->postPageCount(); $page++) {
            $sitemaps->posts($page);
        }
        $sitemaps->categories();
        $sitemaps->tags();
        $sitemaps->authors();
        $sitemaps->pages();
        $sitemaps->news();
        for ($page = 2; $page <= $sitemaps->newsPageCount(); $page++) {
            $sitemaps->newsPage($page);
        }
        $sitemaps->images();
        $sitemaps->robots();
        $this->info('Sitemap caches warmed.');

        return self::SUCCESS;
    }
}
