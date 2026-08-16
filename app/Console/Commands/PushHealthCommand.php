<?php

namespace App\Console\Commands;

use App\Models\PushNotificationDelivery;
use App\Models\PushSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class PushHealthCommand extends Command
{
    protected $signature = 'push:health {--json : Emit machine-readable JSON}';

    protected $description = 'Report push configuration, queue/cache readiness, and safe operational counts without sending.';

    public function handle(): int
    {
        $enabled = (bool) config('firebase.messaging.sending_enabled', false);
        $path = config('firebase.messaging.service_account_path');
        $configured = filled(config('firebase.messaging.project_id')) && is_string($path) && is_readable($path);
        $cache = 'ok';
        try {
            $key = 'push:health:'.bin2hex(random_bytes(6));
            Cache::put($key, true, 5);
            if (! Cache::pull($key, false)) {
                $cache = 'unavailable';
            }
        } catch (Throwable) {
            $cache = 'unavailable';
        }

        $cutoff = now()->subMinutes(max(1, (int) config('firebase.maintenance.stuck_after_minutes', 15)));
        $result = [
            'sending' => $enabled ? 'enabled' : 'disabled',
            'firebase_configuration' => $configured ? 'available' : 'unavailable',
            'queue_connection' => (string) config('queue.default'),
            'push_queue' => (string) config('firebase.messaging.queue', 'push'),
            'cache' => $cache,
            'active_subscriptions' => PushSubscription::query()->active()->count(),
            'queued_deliveries' => PushNotificationDelivery::query()->where('status', 'queued')->count(),
            'attempting_deliveries' => PushNotificationDelivery::query()->where('status', 'attempting')->count(),
            'stuck_deliveries' => PushNotificationDelivery::query()->where('status', 'attempting')->where('last_attempted_at', '<=', $cutoff)->count(),
            'retryable_failures' => PushNotificationDelivery::query()->where('status', 'failed')->where('retryable', true)->count(),
            'final_failures' => PushNotificationDelivery::query()->where('status', 'failed')->where('retryable', false)->count(),
        ];
        $healthy = $cache === 'ok' && (! $enabled || $configured);
        $result['status'] = $healthy ? 'healthy' : 'degraded';

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_THROW_ON_ERROR));
        } else {
            foreach ($result as $key => $value) {
                $this->line(str($key)->replace('_', ' ')->title().': '.$value);
            }
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
