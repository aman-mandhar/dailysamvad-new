<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Jobs\ProcessAnalyticsEvent;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AnalyticsService
{
    public function record(Post $post, array $input, ?string $visitorUuid): bool
    {
        if (! config('analytics.enabled') || $post->status !== PostStatus::Published || ! $post->published_at || $post->published_at->isFuture()) {
            return false;
        } $eventId = (string) ($input['event_id'] ?? '');
        if (! Str::isUuid($eventId)) {
            return false;
        } if (! Cache::add('analytics:event:v1:'.$eventId, 1, now()->addDay())) {
            return false;
        } $ua = (string) request()->userAgent();
        $bot = (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit/i', $ua);
        $internal = auth()->check() && auth()->user()->can('access admin panel');
        $key = 'analytics:dedupe:v1:'.$post->id.':'.hash('sha256', (string) $visitorUuid);
        $unique = Cache::add($key, 1, now()->addMinutes(config('analytics.dedupe_minutes', 30)));
        if (! $bot && ! $internal) {
            Post::query()->whereKey($post)->increment('views_count');
        } ProcessAnalyticsEvent::dispatch($eventId, $post->id, $visitorUuid, $bot, $internal, $unique, now()->toIso8601String());

        return true;
    }
}
