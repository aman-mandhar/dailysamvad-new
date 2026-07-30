<?php

namespace App\Services\Advertisements;

use Illuminate\Support\Facades\Cache;
use Throwable;

class AdvertisementCacheService
{
    public function version(): int
    {
        try {
            return (int) Cache::store(config('cache_architecture.store'))->get('advertisements:resolver-version', 1);
        } catch (Throwable) {
            return (int) Cache::get('advertisements:resolver-version', 1);
        }
    }

    public function invalidate(): void
    {
        $version = $this->version() + 1;
        try {
            Cache::store(config('cache_architecture.store'))->forever('advertisements:resolver-version', $version);
        } catch (Throwable) {
            Cache::forever('advertisements:resolver-version', $version);
        }
    }
}
