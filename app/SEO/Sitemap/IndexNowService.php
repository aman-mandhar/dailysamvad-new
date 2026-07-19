<?php

namespace App\SEO\Sitemap;

use App\Jobs\SubmitIndexNowUrls;

class IndexNowService
{
    public function __construct(private readonly SitemapUrlValidator $urls) {}

    public function enabled(): bool
    {
        return (bool) config('seo.indexnow.enabled') && $this->validKey() !== null;
    }

    public function validKey(): ?string
    {
        $key = config('seo.indexnow.key');

        return is_string($key) && preg_match('/^[A-Za-z0-9-]{8,128}$/', $key) ? $key : null;
    }

    public function submit(array $urls): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        $urls = collect($urls)->map(fn (mixed $url): ?string => $this->urls->validate($url))->filter()->unique()->take(10000)->values()->all();
        if ($urls === []) {
            return false;
        }

        SubmitIndexNowUrls::dispatch($urls);

        return true;
    }
}
