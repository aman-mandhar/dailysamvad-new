<?php

namespace Tests\Feature;

use App\Services\YouTubePlaylistService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class YouTubePlaylistServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('youtube.playlist_id', 'PLKQKUirmHvMkEU4L8tS9H3Bi6ex9f89qr');
        config()->set('youtube.api_key', 'server-only-test-key');
        config()->set('youtube.cache_ttl', 1800);
        config()->set('youtube.failure_cache_ttl', 60);
    }

    public function test_it_normalizes_filters_sorts_and_selects_the_latest_video(): void
    {
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'items' => [
                    $this->item('olderVideo1', '2026-07-01T10:00:00Z'),
                    $this->item('privateVid1', '2026-07-05T10:00:00Z', 'Private video', 'private'),
                    $this->item('newestVideo', '2026-07-10T10:00:00Z'),
                    $this->item('deletedVid1', '2026-07-11T10:00:00Z', 'Deleted video'),
                    $this->item('invalid', '2026-07-12T10:00:00Z'),
                ],
            ]),
        ]);

        $playlist = app(YouTubePlaylistService::class)->playlist();

        $this->assertSame('newestVideo', $playlist['latest_video_id']);
        $this->assertSame(['newestVideo', 'olderVideo1'], $playlist['video_ids']);
        $this->assertNotNull($playlist['fetched_at']);
    }

    public function test_it_follows_pagination_and_uses_the_cache(): void
    {
        Http::fakeSequence()
            ->push([
                'items' => [$this->item('newestVideo', '2026-07-10T10:00:00Z')],
                'nextPageToken' => 'page-two',
            ])
            ->push([
                'items' => [$this->item('olderVideo1', '2026-07-01T10:00:00Z')],
            ]);

        $service = app(YouTubePlaylistService::class);

        $this->assertSame(['newestVideo', 'olderVideo1'], $service->playlist()['video_ids']);
        $this->assertSame(['newestVideo', 'olderVideo1'], $service->playlist()['video_ids']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request): bool => ($request['pageToken'] ?? null) === 'page-two');
    }

    public function test_missing_api_key_returns_a_safe_playlist_fallback_without_an_http_call(): void
    {
        config()->set('youtube.api_key', null);
        Http::preventStrayRequests();

        $playlist = app(YouTubePlaylistService::class)->playlist();

        $this->assertSame(config('youtube.playlist_id'), $playlist['playlist_id']);
        $this->assertNull($playlist['latest_video_id']);
        $this->assertSame([], $playlist['video_ids']);
    }

    public function test_api_failure_is_logged_cached_and_falls_back_safely(): void
    {
        Log::spy();
        Http::fake(['www.googleapis.com/*' => Http::response(['error' => 'quota'], 403)]);

        $service = app(YouTubePlaylistService::class);
        $first = $service->playlist();
        $second = $service->playlist();

        $this->assertNull($first['latest_video_id']);
        $this->assertSame($first, $second);
        Http::assertSentCount(2);
        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $message === 'YouTube playlist refresh failed.'
                && $context['playlist_id'] === config('youtube.playlist_id'),
        );
    }

    /** @return array<string, mixed> */
    private function item(string $videoId, string $publishedAt, string $title = 'Playable video', string $privacy = 'public'): array
    {
        return [
            'snippet' => [
                'title' => $title,
                'publishedAt' => '2026-01-01T00:00:00Z',
                'resourceId' => ['videoId' => $videoId],
            ],
            'contentDetails' => [
                'videoId' => $videoId,
                'videoPublishedAt' => $publishedAt,
            ],
            'status' => ['privacyStatus' => $privacy],
        ];
    }
}
