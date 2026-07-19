<?php

namespace App\SEO\Sitemap;

use Closure;
use Illuminate\Support\Facades\Cache;

class SitemapCache
{
    private static int $batchDepth = 0;

    private static bool $batchDirty = false;

    public function version(): int
    {
        return (int) Cache::get('seo:sitemaps:version', 1);
    }

    public function key(string $name): string
    {
        $context = hash('xxh3', app()->environment().'|'.config('app.url').'|'.request()->getSchemeAndHttpHost());

        return "seo:sitemaps:{$context}:v{$this->version()}:{$name}";
    }

    public function invalidate(): void
    {
        if (self::$batchDepth > 0) {
            self::$batchDirty = true;

            return;
        }

        Cache::forever('seo:sitemaps:version', $this->version() + 1);
    }

    public function beginBatch(): void
    {
        self::$batchDepth++;
    }

    public function endBatch(): void
    {
        self::$batchDepth = max(0, self::$batchDepth - 1);
        if (self::$batchDepth === 0 && self::$batchDirty) {
            self::$batchDirty = false;
            $this->invalidate();
        }
    }

    public function batching(): bool
    {
        return self::$batchDepth > 0;
    }

    public function batch(Closure $callback): mixed
    {
        $this->beginBatch();
        try {
            return $callback();
        } finally {
            $this->endBatch();
        }
    }
}
