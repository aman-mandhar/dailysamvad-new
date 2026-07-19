<?php

namespace App\SEO\Sitemap\Commands;

use App\SEO\Sitemap\SitemapManager;
use Illuminate\Console\Command;

class ValidateSitemapsCommand extends Command
{
    protected $signature = 'seo:sitemaps:validate';

    protected $description = 'Validate locally generated sitemap XML and robots policy';

    public function handle(SitemapManager $sitemaps): int
    {
        $documents = ['index' => $sitemaps->index(), 'news' => $sitemaps->news(), 'images' => $sitemaps->images(), 'pages' => $sitemaps->pages()];
        if ($sitemaps->postPageCount() > 0) {
            $documents['posts-1'] = $sitemaps->posts(1);
        }
        for ($page = 2; $page <= $sitemaps->newsPageCount(); $page++) {
            $documents["news-$page"] = $sitemaps->newsPage($page);
        }
        foreach ($documents as $name => $xml) {
            if ($xml === null || simplexml_load_string($xml) === false) {
                $this->error("Invalid XML: $name");

                return self::FAILURE;
            }
        }
        if (! str_contains($sitemaps->robots(), 'Sitemap: '.route('sitemap'))) {
            $this->error('robots.txt does not reference the sitemap index.');

            return self::FAILURE;
        }

        $this->info('Sitemap XML and robots policy are valid locally.');

        return self::SUCCESS;
    }
}
