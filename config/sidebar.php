<?php

return [
    'homepage' => [
        'sticky' => true,
        'widgets' => [
            ['key' => 'home-sidebar-top-ad', 'type' => 'advertisement', 'enabled' => true, 'slot' => 'HOME_SIDEBAR_TOP'],
            ['key' => 'latest-news', 'type' => 'latest-news', 'enabled' => true, 'title' => 'लेटेस्ट न्यूज़', 'limit' => 6, 'show_category' => true, 'show_date' => true],
            ['key' => 'home-sidebar-middle-ad', 'type' => 'advertisement', 'enabled' => true, 'slot' => 'HOME_SIDEBAR_MIDDLE'],
            ['key' => 'popular-news', 'type' => 'popular-news', 'enabled' => true, 'title' => 'लोकप्रिय खबरें', 'limit' => 6, 'show_category' => false, 'show_date' => true],
            ['key' => 'category-list', 'type' => 'categories', 'enabled' => true, 'title' => 'श्रेणियां', 'limit' => 15, 'show_count' => true],
            ['key' => 'home-sidebar-bottom-ad', 'type' => 'advertisement', 'enabled' => true, 'slot' => 'HOME_SIDEBAR_BOTTOM'],
            ['key' => 'social-follow', 'type' => 'social-follow', 'enabled' => true, 'title' => 'हमें फॉलो करें'],
        ],
    ],
    'article' => [
        'sticky' => true,
        'widgets' => [
            ['key' => 'article-sidebar-ad', 'type' => 'advertisement', 'enabled' => true, 'slot' => 'ARTICLE_SIDEBAR'],
            ['key' => 'article-sidebar-top-ad', 'type' => 'advertisement', 'enabled' => true, 'slot' => 'ARTICLE_SIDEBAR_TOP'],
            ['key' => 'article-latest-news', 'type' => 'latest-news', 'enabled' => true, 'title' => 'Latest News', 'limit' => 6, 'show_category' => true, 'show_date' => true],
            ['key' => 'article-popular-news', 'type' => 'popular-news', 'enabled' => true, 'title' => 'Popular News', 'limit' => 6, 'show_category' => false, 'show_date' => true],
            ['key' => 'article-sidebar-bottom-ad', 'type' => 'advertisement', 'enabled' => true, 'slot' => 'ARTICLE_SIDEBAR_BOTTOM'],
            ['key' => 'article-categories', 'type' => 'categories', 'enabled' => true, 'title' => 'Categories', 'limit' => 15, 'show_count' => true],
            ['key' => 'article-social-follow', 'type' => 'social-follow', 'enabled' => true, 'title' => 'Follow Us'],
        ],
    ],
    'archive' => [
        'sticky' => true,
        'widgets' => [
            ['key' => 'archive-latest-news', 'type' => 'latest-news', 'enabled' => true, 'title' => 'Latest News', 'limit' => 6, 'show_category' => true, 'show_date' => true],
            ['key' => 'archive-popular-news', 'type' => 'popular-news', 'enabled' => true, 'title' => 'Popular News', 'limit' => 6, 'show_category' => false, 'show_date' => true],
            ['key' => 'archive-categories', 'type' => 'categories', 'enabled' => true, 'title' => 'Categories', 'limit' => 15, 'show_count' => true],
            ['key' => 'archive-social-follow', 'type' => 'social-follow', 'enabled' => true, 'title' => 'Follow Us'],
        ],
    ],
];
