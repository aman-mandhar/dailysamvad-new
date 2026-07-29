<?php

namespace App\View\Components;

use App\Services\YouTubePlaylistService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class YouTubePlaylistPlayer extends Component
{
    /** @var array{playlist_id: string, latest_video_id: ?string, video_ids: array<int, string>, fetched_at: ?string} */
    public array $playlist;

    public string $playerId;

    /** @var array<string, mixed> */
    public array $playerConfig;

    public function __construct(YouTubePlaylistService $playlists, public string $placement = 'homepage')
    {
        $this->playlist = $playlists->playlist();
        $this->playerId = 'youtube-playlist-player-'.Str::lower((string) Str::ulid());
        $this->playerConfig = [
            'playlistId' => $this->playlist['playlist_id'],
            'videoIds' => $this->playlist['video_ids'],
            'latestVideoId' => $this->playlist['latest_video_id'],
            'autoplay' => (bool) config('youtube.autoplay', true),
            'muted' => (bool) config('youtube.muted', true),
            'loop' => (bool) config('youtube.loop', true),
        ];
    }

    public function shouldRender(): bool
    {
        return $this->playlist['playlist_id'] !== '';
    }

    public function embedUrl(): string
    {
        $video = $this->playlist['latest_video_id'];
        $path = $video ? 'embed/'.$video : 'embed/videoseries';
        $parameters = http_build_query([
            'autoplay' => (int) config('youtube.autoplay', true),
            'mute' => (int) config('youtube.muted', true),
            'playsinline' => 1,
            'enablejsapi' => 1,
            'rel' => 0,
            'controls' => 1,
            'loop' => (int) config('youtube.loop', true),
            'listType' => 'playlist',
            'list' => $this->playlist['playlist_id'],
        ], '', '&', PHP_QUERY_RFC3986);

        return 'https://www.youtube-nocookie.com/'.$path.'?'.$parameters;
    }

    public function playlistUrl(): string
    {
        return 'https://www.youtube.com/playlist?list='.rawurlencode($this->playlist['playlist_id']);
    }

    public function render(): View
    {
        return view('components.youtube-playlist-player');
    }
}
