<?php

namespace App\Services\Advertisements;

use App\Models\Advertisement;
use App\Models\AdvertisementDailyStat;
use Illuminate\Support\Facades\Cache;

class AdvertisementTrackingService
{
    public function record(Advertisement $advertisement, string $metric, ?string $visitorToken): bool
    {
        if (! $advertisement->isRenderable() || ! in_array($metric, ['impressions', 'clicks'], true)) {
            return false;
        }
        $visitor = hash('sha256', (string) $visitorToken.'|'.request()->ip().'|'.request()->userAgent());
        $dedupeKey = "advertisements:tracking:$metric:{$advertisement->uuid}:".now()->toDateString().":$visitor";
        if (! Cache::add($dedupeKey, true, now()->addDay())) {
            return false;
        }
        $date = now()->toDateString();
        $stat = AdvertisementDailyStat::query()->where('advertisement_id', $advertisement->getKey())->whereDate('date', $date)->first()
            ?? AdvertisementDailyStat::query()->create(['advertisement_id' => $advertisement->getKey(), 'date' => $date]);
        $unique = $metric === 'impressions' ? 'unique_impressions' : 'unique_clicks';
        AdvertisementDailyStat::query()->whereKey($stat->getKey())->incrementEach([$metric => 1, $unique => 1]);

        return true;
    }
}
