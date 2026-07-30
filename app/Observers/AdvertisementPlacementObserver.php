<?php

namespace App\Observers;

use App\Models\AdvertisementPlacement;
use App\Services\Advertisements\AdvertisementCacheService;

class AdvertisementPlacementObserver
{
    public function saved(AdvertisementPlacement $placement): void
    {
        app(AdvertisementCacheService::class)->invalidate();
    }

    public function deleted(AdvertisementPlacement $placement): void
    {
        app(AdvertisementCacheService::class)->invalidate();
    }
}
