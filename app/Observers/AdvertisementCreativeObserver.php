<?php

namespace App\Observers;

use App\Models\AdvertisementAudit;
use App\Models\AdvertisementCreative;
use App\Services\Advertisements\AdvertisementCacheService;
use Illuminate\Validation\ValidationException;

class AdvertisementCreativeObserver
{
    public function saving(AdvertisementCreative $creative): void
    {
        if ($creative->type === 'video' && $creative->autoplay && ! $creative->muted) {
            throw ValidationException::withMessages(['muted' => 'Autoplay video advertisements must be muted.']);
        }
        if (! in_array($creative->type, ['image', 'video', 'html', 'provider_code'], true)) {
            throw ValidationException::withMessages(['type' => 'Unsupported advertisement creative type.']);
        }
    }

    public function created(AdvertisementCreative $creative): void
    {
        $this->changed($creative, 'creative_replaced');
    }

    public function updated(AdvertisementCreative $creative): void
    {
        $this->changed($creative, 'creative_replaced');
    }

    public function deleted(AdvertisementCreative $creative): void
    {
        app(AdvertisementCacheService::class)->invalidate();
    }

    private function changed(AdvertisementCreative $creative, string $action): void
    {
        app(AdvertisementCacheService::class)->invalidate();
        AdvertisementAudit::query()->create(['advertisement_id' => $creative->advertisement_id, 'user_id' => auth()->id(), 'action' => $action, 'new_values' => ['creative_id' => $creative->getKey(), 'type' => $creative->type]]);
    }
}
