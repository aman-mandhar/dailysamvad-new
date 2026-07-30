<?php

namespace App\Observers;

use App\Models\Advertisement;
use App\Models\AdvertisementAudit;
use App\Services\Advertisements\AdvertisementCacheService;

class AdvertisementObserver
{
    public function created(Advertisement $advertisement): void
    {
        $this->record($advertisement, 'created', null, $advertisement->getAttributes());
    }

    public function updated(Advertisement $advertisement): void
    {
        $changes = $advertisement->getChanges();
        $action = array_key_exists('target_url', $changes)
            ? 'link_changed'
            : (array_intersect_key($changes, array_flip(['start_at', 'end_at']))
                ? 'schedule_changed'
                : ((($changes['status'] ?? null) === 'paused') ? 'paused' : 'updated'));
        $this->record($advertisement, $action, array_intersect_key($advertisement->getOriginal(), $changes), $changes);
    }

    public function deleted(Advertisement $advertisement): void
    {
        $this->record($advertisement, 'deleted');
    }

    public function restored(Advertisement $advertisement): void
    {
        $this->record($advertisement, 'restored');
    }

    private function record(Advertisement $advertisement, string $action, ?array $old = null, ?array $new = null): void
    {
        app(AdvertisementCacheService::class)->invalidate();
        if (! $advertisement->exists) {
            return;
        }
        AdvertisementAudit::query()->create(['advertisement_id' => $advertisement->getKey(), 'user_id' => auth()->id(), 'action' => $action, 'old_values' => $old, 'new_values' => $new, 'ip_hash' => request()?->ip() ? hash('sha256', request()->ip().'|'.config('app.key')) : null]);
    }
}
