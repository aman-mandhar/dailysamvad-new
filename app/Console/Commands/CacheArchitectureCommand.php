<?php

namespace App\Console\Commands;

use App\Services\CacheInvalidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CacheArchitectureCommand extends Command
{
    protected $signature = 'cache:architecture {operation : inspect|invalidate|warm} {key?}';
    protected $description = 'Inspect, target-invalidate, or safely warm Rzana Punjab cache families.';

    public function handle(): int
    {
        return match ($this->argument('operation')) {
            'inspect' => $this->inspect(),
            'invalidate' => $this->invalidate(),
            'warm' => $this->warm(),
            default => $this->invalidArgument('operation'),
        };
    }

    private function inspect(): int
    {
        $key = $this->argument('key');
        if (! $key || ! str_contains($key, ':')) return $this->invalidArgument('key', 'Provide one fully qualified, namespaced cache key.');
        try { $this->line(json_encode(['key' => $key, 'present' => Cache::get($key) !== null], JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        catch (Throwable) { $this->error('Cache inspection is unavailable.'); return self::FAILURE; }
    }

    private function invalidate(): int
    {
        $family = $this->argument('key');
        $service = app(CacheInvalidationService::class);
        match ($family) { 'sitemaps' => $service->invalidateSitemaps(), 'homepage', 'public' => $service->invalidateTaxonomy(), default => $service->invalidatePost((int) ($family ?: 0)) };
        $this->info('Targeted cache invalidation requested.');
        return self::SUCCESS;
    }

    private function warm(): int
    {
        app(\App\SEO\Sitemap\SitemapManager::class)->index();
        $this->info('Bounded sitemap cache warm requested.');
        return self::SUCCESS;
    }

    private function invalidArgument(string $argument, string $message = 'Invalid argument.'): int { $this->error($message); return self::INVALID;
    }
}
