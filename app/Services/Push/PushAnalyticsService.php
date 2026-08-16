<?php

namespace App\Services\Push;

use App\Models\PushNotification;
use App\Models\PushNotificationDelivery;

class PushAnalyticsService
{
    /** @return array{recipients:int,queued:int,attempted:int,accepted:int,failed:int,retryable_failures:int,unique_clicks:int,total_clicks:int,ctr:float,failure_categories:array<string,int>} */
    public function summary(PushNotification $notification): array
    {
        $base = PushNotificationDelivery::query()->where('push_notification_id', $notification->getKey());
        $aggregate = (clone $base)->selectRaw("COUNT(*) recipients, SUM(status = 'queued') queued, SUM(attempt_count > 0) attempted, SUM(status = 'accepted') accepted, SUM(status = 'failed') failed, SUM(status = 'failed' AND retryable = 1) retryable_failures, SUM(first_clicked_at IS NOT NULL) unique_clicks, COALESCE(SUM(click_count), 0) total_clicks")->first();
        $accepted = (int) ($aggregate->accepted ?? 0);
        $unique = (int) ($aggregate->unique_clicks ?? 0);

        return [
            'recipients' => (int) ($aggregate->recipients ?? 0),
            'queued' => (int) ($aggregate->queued ?? 0),
            'attempted' => (int) ($aggregate->attempted ?? 0),
            'accepted' => $accepted,
            'failed' => (int) ($aggregate->failed ?? 0),
            'retryable_failures' => (int) ($aggregate->retryable_failures ?? 0),
            'unique_clicks' => $unique,
            'total_clicks' => (int) ($aggregate->total_clicks ?? 0),
            'ctr' => $accepted === 0 ? 0.0 : round(($unique / $accepted) * 100, 2),
            'failure_categories' => (clone $base)->whereNotNull('error_category')->select('error_category')->selectRaw('COUNT(*) as aggregate')->groupBy('error_category')->pluck('aggregate', 'error_category')->map(fn ($count): int => (int) $count)->all(),
        ];
    }

    public function errorCategory(?string $code, bool $tokenInvalid): string
    {
        if ($tokenInvalid || $code === 'UNREGISTERED') {
            return 'invalid_token';
        }

        return match ($code) {
            'UNAUTHENTICATED', 'PERMISSION_DENIED', 'CONFIGURATION_ERROR' => 'authentication',
            'RESOURCE_EXHAUSTED', 'QUOTA_EXCEEDED' => 'quota',
            'UNAVAILABLE', 'INTERNAL' => 'server',
            'NETWORK_ERROR' => 'network',
            'INVALID_ARGUMENT', 'INVALID_RESPONSE' => 'invalid_request',
            'INACTIVE_SUBSCRIPTION', 'MISSING_TOKEN' => 'subscription',
            default => 'unknown',
        };
    }
}
