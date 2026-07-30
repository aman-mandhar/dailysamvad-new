<?php

return [
    'playlist_id' => env('YOUTUBE_PLAYLIST_ID', 'PLKQKUirmHvMkEU4L8tS9H3Bi6ex9f89qr'),
    'api_key' => env('YOUTUBE_API_KEY'),
    'cache_ttl' => max(60, (int) env('YOUTUBE_PLAYLIST_CACHE_TTL', 1800)),
    'failure_cache_ttl' => max(30, (int) env('YOUTUBE_PLAYLIST_FAILURE_CACHE_TTL', 60)),
    'connect_timeout' => max(1, (int) env('YOUTUBE_API_CONNECT_TIMEOUT', 3)),
    'timeout' => max(1, (int) env('YOUTUBE_API_TIMEOUT', 8)),
    'autoplay' => (bool) env('YOUTUBE_PLAYER_AUTOPLAY', true),
    'muted' => (bool) env('YOUTUBE_PLAYER_MUTED', true),
    'loop' => (bool) env('YOUTUBE_PLAYER_LOOP', true),
];
