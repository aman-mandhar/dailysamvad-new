<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Media;
use App\Models\Tag;
use App\Models\User;
use App\SEO\Sitemap\SitemapCache;
use Illuminate\Database\Eloquent\Model;

class SitemapObserver
{
    public function saved(Model $model): void
    {
        if ($model->wasRecentlyCreated || $this->relevantChange($model)) {
            app(SitemapCache::class)->invalidate();
        }
    }

    public function deleted(Model $model): void
    {
        app(SitemapCache::class)->invalidate();
    }

    public function restored(Model $model): void
    {
        app(SitemapCache::class)->invalidate();
    }

    private function relevantChange(Model $model): bool
    {
        return match (true) {
            $model instanceof Category => $model->wasChanged(['slug', 'is_active', 'name']),
            $model instanceof Tag => $model->wasChanged(['slug', 'name']),
            $model instanceof User => $model->wasChanged(['username', 'name', 'is_public', 'is_active']),
            $model instanceof Media => $model->wasChanged(['path', 'disk', 'alt_text', 'caption', 'missing_at']),
            default => true,
        };
    }
}
