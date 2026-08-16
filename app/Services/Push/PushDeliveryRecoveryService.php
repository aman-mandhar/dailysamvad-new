<?php

namespace App\Services\Push;

use App\Jobs\Push\SendPushNotificationJob;
use App\Models\PushNotificationDelivery;
use Illuminate\Support\Carbon;
use Throwable;

class PushDeliveryRecoveryService
{
    /** @return array{candidates:int,requeued:int,failed:int} */
    public function recover(bool $dryRun = false, ?int $limit = null): array
    {
        $limit ??= max(1, (int) config('firebase.maintenance.batch_size', 500));
        $cutoff = Carbon::now()->subMinutes(max(1, (int) config('firebase.maintenance.stuck_after_minutes', 15)));
        $ids = PushNotificationDelivery::query()
            ->where('status', 'attempting')
            ->where('last_attempted_at', '<=', $cutoff)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        if ($dryRun) {
            return ['candidates' => $ids->count(), 'requeued' => 0, 'failed' => 0];
        }

        $requeued = 0;
        $failed = 0;
        foreach ($ids as $id) {
            $claimed = PushNotificationDelivery::query()
                ->whereKey($id)
                ->where('status', 'attempting')
                ->where('last_attempted_at', '<=', $cutoff)
                ->update([
                    'status' => 'queued',
                    'error_code' => 'STALE_ATTEMPT_RECOVERED',
                    'error_category' => 'queue',
                    'error_message' => 'A stale delivery attempt was recovered for retry.',
                    'retryable' => true,
                    'failed_at' => null,
                    'updated_at' => now(),
                ]);
            if ($claimed !== 1) {
                continue;
            }

            $delivery = PushNotificationDelivery::query()->find($id);
            if ($delivery === null || $delivery->push_subscription_id === null) {
                PushNotificationDelivery::query()->whereKey($id)->where('status', 'queued')->update([
                    'status' => 'failed',
                    'error_code' => 'MISSING_SUBSCRIPTION',
                    'error_category' => 'subscription',
                    'error_message' => 'The subscription no longer exists.',
                    'retryable' => false,
                    'failed_at' => now(),
                    'updated_at' => now(),
                ]);
                $failed++;

                continue;
            }

            try {
                SendPushNotificationJob::dispatch($delivery->push_subscription_id, [], $delivery->getKey());
                $requeued++;
            } catch (Throwable) {
                PushNotificationDelivery::query()->whereKey($id)->where('status', 'queued')->update([
                    'status' => 'failed',
                    'error_code' => 'RECOVERY_QUEUE_FAILURE',
                    'error_category' => 'queue',
                    'error_message' => 'The recovered delivery could not be queued.',
                    'retryable' => true,
                    'failed_at' => now(),
                    'updated_at' => now(),
                ]);
                $failed++;
            }
        }

        return ['candidates' => $ids->count(), 'requeued' => $requeued, 'failed' => $failed];
    }
}
