<?php

namespace App\SEO\Sitemap\Commands;

use App\SEO\Sitemap\SitemapManager;
use Illuminate\Console\Command;

class ClearSitemapsCommand extends Command
{
    protected $signature = 'seo:sitemaps:clear';

    protected $description = 'Invalidate only sitemap and robots cache keys';

    public function handle(SitemapManager $sitemaps): int
    {
        $sitemaps->invalidate();
        $this->info('Sitemap caches invalidated.');

        return self::SUCCESS;
    }
}
