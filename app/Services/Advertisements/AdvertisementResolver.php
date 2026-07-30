<?php

namespace App\Services\Advertisements;

use App\Data\AdvertisementData;
use App\Enums\AdvertisementPosition;
use App\Models\Advertisement;
use App\Models\AdvertisementCreative;
use App\Models\Post;
use App\Support\AdvertisementUrl;
use App\Support\MediaUrlResolver;
use App\Support\SafeAdvertisementHtml;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdvertisementResolver
{
    public function __construct(private readonly AdvertisementCacheService $cache, private readonly MediaUrlResolver $mediaUrls, private readonly SafeAdvertisementHtml $html) {}

    /** @param array<string, mixed>|object|null $context */
    public function resolve(AdvertisementPosition|string $position, array|object|null $context = null, ?string $device = null): AdvertisementData
    {
        $slot = $position instanceof AdvertisementPosition ? $position->value : $position;
        $dimensions = $this->contextDimensions($context);
        $device ??= $this->detectDevice(request()->userAgent());
        $key = 'advertisements:'.$this->cache->version().':'.hash('sha256', json_encode([$slot, $dimensions, $device, now()->format('YmdHi')]));

        $callback = function () use ($slot, $dimensions, $device): AdvertisementData {
            $resolved = $this->resolveDatabase($slot, $dimensions, $device);

            return $resolved ?? AdvertisementData::fromConfig($slot, (array) config("advertisements.slots.$slot", []), (bool) config('advertisements.show_placeholders', false));
        };
        try {
            return $this->resolveCached(Cache::store(config('cache_architecture.store')), $key, $callback);
        } catch (Throwable) {
            return $this->resolveCached(Cache::store(), $key, $callback);
        }
    }

    /** @param callable(): AdvertisementData $callback */
    private function resolveCached(Repository $cache, string $key, callable $callback): AdvertisementData
    {
        $cached = $cache->get($key);
        if (is_array($cached)) {
            return AdvertisementData::fromCacheArray($cached);
        }
        if ($cached !== null) {
            $cache->forget($key);
        }

        $advertisement = $callback();
        $cache->put($key, $advertisement->toCacheArray(), now()->addSeconds((int) config('advertisements.cache_ttl', 60)));

        return $advertisement;
    }

    /** @param array{page_type:?string,post_id:?int,category_id:?int,tag_ids:list<int>} $context */
    private function resolveDatabase(string $slot, array $context, string $device): ?AdvertisementData
    {
        if (! Schema::hasTable('advertisements') || ! Schema::hasTable('advertisement_placements')) {
            return null;
        }
        $ads = Advertisement::query()->active()->currentlyScheduled()->forPosition($slot)
            ->whereHas('placements', function (Builder $query) use ($slot, $context, $device): void {
                $query->where('position', $slot)->whereIn('device', ['all', $device])
                    ->where(fn (Builder $q) => $q->whereNull('page_type')->orWhere('page_type', $context['page_type']))
                    ->where(fn (Builder $q) => $q->whereNull('post_id')->orWhere('post_id', $context['post_id']))
                    ->where(fn (Builder $q) => $q->whereNull('category_id')->orWhere('category_id', $context['category_id']))
                    ->where(fn (Builder $q) => $q->whereNull('tag_id')->when($context['tag_ids'] !== [], fn (Builder $q) => $q->orWhereIn('tag_id', $context['tag_ids'])));
            })->with(['creative.media', 'creative.posterMedia'])->orderByDesc('priority')->get()->filter(fn (Advertisement $ad) => $this->creativeIsValid($ad->creative));
        if ($ads->isEmpty()) {
            return null;
        }
        $highest = $ads->max('priority');
        $pool = $ads->where('priority', $highest)->values();
        $total = max(1, $pool->sum('rotation_weight'));
        $point = abs(crc32($slot.'|'.now()->format('YmdHi'))) % $total;
        $selected = $pool->last();
        foreach ($pool as $candidate) {
            $point -= max(1, $candidate->rotation_weight);
            if ($point < 0) {
                $selected = $candidate;
                break;
            }
        }

        return $this->toData($selected, $slot);
    }

    private function creativeIsValid(?AdvertisementCreative $creative): bool
    {
        if (! $creative) {
            return false;
        }

        return match ($creative->type) {
            'image' => $this->creativeMediaUrl($creative, false) !== null,
            'video' => $this->creativeMediaUrl($creative, true) !== null && in_array($creative->mime_type, [null, 'video/mp4', 'video/webm'], true),
            'html' => $this->html->sanitize($creative->html_code) !== null,
            'provider_code' => filled($creative->html_code),
            default => false,
        };
    }

    private function toData(Advertisement $ad, string $slot): AdvertisementData
    {
        $creative = $ad->creative;
        $type = $creative->type;
        $image = $type === 'image' ? $this->creativeMediaUrl($creative, false) : null;
        $video = $type === 'video' ? $this->creativeMediaUrl($creative, true) : null;
        $poster = $creative->posterMedia ? $this->mediaUrls->resolveExisting($creative->posterMedia->path, $creative->posterMedia->disk) : $this->mediaUrls->resolveExisting($creative->poster_path);
        $html = $type === 'html' ? $this->html->sanitize($creative->html_code) : ($type === 'provider_code' ? $creative->html_code : null);
        $target = AdvertisementUrl::normalize($ad->target_url);
        $rel = array_values(array_filter([$ad->sponsored ? 'sponsored' : null, $ad->nofollow ? 'nofollow' : null, $ad->open_in_new_tab ? 'noopener noreferrer' : null]));

        return new AdvertisementData($slot, true, $type, 'Advertisement', $html, $image, $target, (string) ($creative->alt_text ?: $ad->title), (int) ($creative->width ?: 300), (int) ($creative->height ?: 250), $ad->open_in_new_tab, implode(' ', $rel), $ad->getKey(), $ad->uuid, $video, $poster, $creative->autoplay, $creative->muted, $creative->loop, $creative->controls, $target ? route('advertisements.click', $ad->uuid) : null, route('advertisements.impression', $ad->uuid), null, false);
    }

    private function creativeMediaUrl(AdvertisementCreative $creative, bool $video): ?string
    {
        if ($creative->media && ! $creative->media->missing_at) {
            return $this->mediaUrls->resolveExisting($creative->media->path, $creative->media->disk);
        }

        return $this->mediaUrls->resolveExisting($video ? $creative->video_path : $creative->image_path);
    }

    /** @return array{page_type:?string,post_id:?int,category_id:?int,tag_ids:list<int>} */
    private function contextDimensions(array|object|null $context): array
    {
        if ($context instanceof Post) {
            return ['page_type' => 'article', 'post_id' => $context->getKey(), 'category_id' => $context->primaryCategory->first()?->getKey(), 'tag_ids' => $context->tags->modelKeys()];
        }
        $data = is_array($context) ? $context : [];

        return ['page_type' => $data['page_type'] ?? null, 'post_id' => isset($data['post_id']) ? (int) $data['post_id'] : null, 'category_id' => isset($data['category_id']) ? (int) $data['category_id'] : null, 'tag_ids' => array_map('intval', $data['tag_ids'] ?? [])];
    }

    private function detectDevice(?string $agent): string
    {
        $agent = strtolower((string) $agent);
        if (preg_match('/ipad|tablet/', $agent)) {
            return 'tablet';
        }
        if (preg_match('/mobile|android|iphone|ipod/', $agent)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
