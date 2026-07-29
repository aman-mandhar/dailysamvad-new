<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Cache\LockTimeoutException;
use Illuminate\Cache\Repository;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class YouTubePlaylistService
{
    private const API_URL = 'https://www.googleapis.com/youtube/v3/playlistItems';

    /** @return array{playlist_id: string, latest_video_id: ?string, video_ids: array<int, string>, fetched_at: ?string} */
    public function playlist(): array
    {
        $playlistId = $this->playlistId();

        if ($playlistId === null) {
            return $this->fallback('');
        }

        $apiKey = trim((string) config('youtube.api_key'));

        if ($apiKey === '') {
            return $this->fallback($playlistId);
        }

        $cache = $this->cache();
        $key = 'youtube:playlist:v1:'.$playlistId;

        if ($cached = $this->validCachedValue($cache->get($key), $playlistId)) {
            return $cached;
        }

        try {
            return $cache->lock($key.':lock', 15)->block(3, function () use ($apiKey, $cache, $key, $playlistId): array {
                if ($cached = $this->validCachedValue($cache->get($key), $playlistId)) {
                    return $cached;
                }

                try {
                    $playlist = $this->fetch($playlistId, $apiKey);
                    $cache->put($key, $playlist, (int) config('youtube.cache_ttl', 1800));

                    return $playlist;
                } catch (Throwable $exception) {
                    Log::warning('YouTube playlist refresh failed.', [
                        'playlist_id' => $playlistId,
                        'exception' => $exception::class,
                        'code' => $exception->getCode(),
                    ]);

                    $fallback = $this->fallback($playlistId);
                    $cache->put($key, $fallback, (int) config('youtube.failure_cache_ttl', 60));

                    return $fallback;
                }
            });
        } catch (LockTimeoutException $exception) {
            Log::notice('YouTube playlist refresh lock timed out.', [
                'playlist_id' => $playlistId,
                'message' => $exception->getMessage(),
            ]);

            return $this->fallback($playlistId);
        } catch (Throwable $exception) {
            Log::warning('YouTube playlist cache failed.', [
                'playlist_id' => $playlistId,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->fallback($playlistId);
        }
    }

    private function playlistId(): ?string
    {
        $playlistId = trim((string) config('youtube.playlist_id'));

        return preg_match('/\A[A-Za-z0-9_-]{10,64}\z/', $playlistId) === 1 ? $playlistId : null;
    }

    private function cache(): Repository
    {
        return Cache::store();
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->connectTimeout((int) config('youtube.connect_timeout', 3))
            ->timeout((int) config('youtube.timeout', 8))
            ->retry(2, 200, throw: false);
    }

    /** @return array{playlist_id: string, latest_video_id: ?string, video_ids: array<int, string>, fetched_at: ?string} */
    private function fetch(string $playlistId, string $apiKey): array
    {
        $items = [];
        $pageToken = null;

        do {
            $query = [
                'part' => 'snippet,contentDetails,status',
                'maxResults' => 50,
                'playlistId' => $playlistId,
                'key' => $apiKey,
            ];

            if (is_string($pageToken) && $pageToken !== '') {
                $query['pageToken'] = $pageToken;
            }

            $response = $this->request()->get(self::API_URL, $query);
            $response->throw();
            $payload = $response->json();

            if (! is_array($payload)) {
                throw new \RuntimeException('YouTube returned an invalid playlist response.');
            }

            foreach ((array) ($payload['items'] ?? []) as $item) {
                if ($normalized = $this->normalizeItem((array) $item)) {
                    $items[] = $normalized;
                }
            }

            $pageToken = $payload['nextPageToken'] ?? null;
        } while (is_string($pageToken) && $pageToken !== '');

        usort($items, static fn (array $left, array $right): int => $right['published_at'] <=> $left['published_at']);

        $videoIds = array_values(array_unique(array_column($items, 'video_id')));

        return [
            'playlist_id' => $playlistId,
            'latest_video_id' => $videoIds[0] ?? null,
            'video_ids' => $videoIds,
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /** @return array{video_id: string, published_at: int}|null */
    private function normalizeItem(array $item): ?array
    {
        $videoId = (string) data_get($item, 'contentDetails.videoId', data_get($item, 'snippet.resourceId.videoId', ''));
        $title = trim((string) data_get($item, 'snippet.title', ''));
        $privacyStatus = strtolower((string) data_get($item, 'status.privacyStatus', 'public'));
        $publishedAt = (string) data_get($item, 'contentDetails.videoPublishedAt', data_get($item, 'snippet.publishedAt', ''));

        if (preg_match('/\A[A-Za-z0-9_-]{11}\z/', $videoId) !== 1
            || in_array(strtolower($title), ['deleted video', 'private video'], true)
            || in_array($privacyStatus, ['private'], true)
            || $publishedAt === '') {
            return null;
        }

        try {
            $timestamp = (new DateTimeImmutable($publishedAt))->getTimestamp();
        } catch (Throwable) {
            return null;
        }

        return ['video_id' => $videoId, 'published_at' => $timestamp];
    }

    /** @return array{playlist_id: string, latest_video_id: ?string, video_ids: array<int, string>, fetched_at: ?string} */
    private function fallback(string $playlistId): array
    {
        return [
            'playlist_id' => $playlistId,
            'latest_video_id' => null,
            'video_ids' => [],
            'fetched_at' => null,
        ];
    }

    /** @return array{playlist_id: string, latest_video_id: ?string, video_ids: array<int, string>, fetched_at: ?string}|null */
    private function validCachedValue(mixed $value, string $playlistId): ?array
    {
        if (! is_array($value)
            || ($value['playlist_id'] ?? null) !== $playlistId
            || ! array_key_exists('latest_video_id', $value)
            || ! is_array($value['video_ids'] ?? null)) {
            return null;
        }

        return $value;
    }
}
