<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PushSubscriptionCleanupService
{
    /** @return array{candidates:int,deleted:int,cutoff:string} */
    public function prune(bool $dryRun = false, ?int $limit = null): array
    {
        $limit ??= max(1, (int) config('firebase.maintenance.batch_size', 500));
        $cutoff = Carbon::now()->subDays(max(1, (int) config('firebase.maintenance.inactive_retention_days', 90)));
        $query = $this->candidates($cutoff);
        $ids = (clone $query)->orderBy('id')->limit($limit)->pluck('id');

        $deleted = $dryRun || $ids->isEmpty()
            ? 0
            : PushSubscription::query()->whereIn('id', $ids)->where('is_active', false)->delete();

        return ['candidates' => $ids->count(), 'deleted' => $deleted, 'cutoff' => $cutoff->toIso8601String()];
    }

    private function candidates(Carbon $cutoff): Builder
    {
        return PushSubscription::query()
            ->where('is_active', false)
            ->where(function (Builder $query) use ($cutoff): void {
                $query->where('unsubscribed_at', '<=', $cutoff)
                    ->orWhere(function (Builder $fallback) use ($cutoff): void {
                        $fallback->whereNull('unsubscribed_at')
                            ->whereRaw('COALESCE(last_seen_at, updated_at) <= ?', [$cutoff]);
                    });
            });
    }
}
