<?php

namespace App\Services;

use App\Support\CacheKey;
use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CacheQueryService
{
    public function __construct(private readonly CacheKey $keys) {}

    public function remember(string $domain, string $resource, string $scope, string|int $identifier, int $ttl, Closure $resolver, array $parameters = []): mixed
    {
        if (! config('cache_architecture.enabled') || ! config('cache_architecture.query')) return $resolver();
        $store = Cache::store(config('cache_architecture.store', 'redis'));
        $key = $this->keys->make($domain, $resource, $scope, $identifier, parameters: $parameters);
        try {
            $existing = $store->get($key);
            if ($existing !== null) return $existing;
            $lock = $store->lock($key.':lock', 10);
            if (! $lock->get()) return $resolver();
            try { return $store->remember($key, $ttl, $resolver); } finally { $lock->release(); }
        } catch (Throwable) { return $resolver(); }
    }
}
