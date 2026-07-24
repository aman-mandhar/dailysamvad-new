<?php

return [
    'enabled' => (bool) env('CACHE_ARCHITECTURE_ENABLED', false),
    'store' => env('CACHE_ARCHITECTURE_STORE', 'redis'),
    'version' => 'v1',
    'full_page' => (bool) env('CACHE_FULL_PAGE_ENABLED', false),
    'query' => (bool) env('CACHE_QUERY_ENABLED', false),
    'dashboard' => (bool) env('CACHE_DASHBOARD_ENABLED', false),
    'fragment' => (bool) env('CACHE_FRAGMENT_ENABLED', false),
    'ttls' => [
        'very_short' => 60,
        'short' => 300,
        'medium' => 1800,
        'long' => 21600,
        'very_long' => 43200,
    ],
    'public_routes' => [
        'home', 'news.show', 'categories.show', 'tags.show', 'authors.show',
        'archives.year', 'archives.month', 'archives.day',
        'static.*', 'feed',
    ],
];
