<?php

namespace App\Observers;

use App\SEO\Sitemap\SitemapCache;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SitemapPivotObserver
{
    public function saved(Pivot $pivot): void
    {
        app(SitemapCache::class)->invalidate();
    }

    public function deleted(Pivot $pivot): void
    {
        app(SitemapCache::class)->invalidate();
    }
}
