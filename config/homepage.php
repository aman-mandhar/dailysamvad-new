<?php

return [
    'sections' => [
        ['key' => 'punjab', 'title' => 'पंजाब', 'slugs' => ['punjab'], 'names' => ['पंजाब', 'Punjab'], 'layout' => 'dual-lead', 'limit' => 8, 'show_meta' => true, 'view_all' => true],
        ['key' => 'haryana', 'title' => 'हरियाणा', 'slugs' => ['haryana'], 'names' => ['हरियाणा', 'Haryana'], 'layout' => 'dual-lead', 'limit' => 8, 'show_meta' => true, 'view_all' => true],
        ['key' => 'delhi', 'title' => 'दिल्ली', 'slugs' => ['delhi'], 'names' => ['दिल्ली', 'Delhi'], 'layout' => 'dual-lead', 'limit' => 8, 'show_meta' => true, 'view_all' => true],
        ['key' => 'uttar-pradesh', 'title' => 'उत्तर प्रदेश', 'slugs' => ['uttar-pradesh'], 'names' => ['उत्तर प्रदेश', 'Uttar Pradesh'], 'layout' => 'dual-lead', 'limit' => 8, 'show_meta' => true, 'view_all' => true],
        ['key' => 'uttarakhand', 'title' => 'उत्तराखंड', 'slugs' => ['uttarakhand'], 'names' => ['उत्तराखंड', 'Uttarakhand'], 'layout' => 'dual-lead', 'limit' => 8, 'show_meta' => true, 'view_all' => true],
        ['key' => 'himachal-pradesh', 'title' => 'हिमाचल प्रदेश', 'slugs' => ['himachal-pradesh'], 'names' => ['हिमाचल प्रदेश', 'Himachal Pradesh'], 'layout' => 'dual-lead', 'limit' => 8, 'show_meta' => true, 'view_all' => true],
        ['key' => 'india', 'title' => 'देश', 'slugs' => ['india', 'desh'], 'names' => ['देश', 'India'], 'layout' => 'compact-list', 'limit' => 8, 'show_meta' => true, 'view_all' => true],
        ['key' => 'world', 'title' => 'दुनिया', 'slugs' => ['world', 'duniya'], 'names' => ['दुनिया', 'World'], 'layout' => 'compact-list', 'limit' => 8, 'show_meta' => true, 'view_all' => true],
        // Phase 6+ insertion boundary: VIDEO NEWS belongs between world and breaking news.
        ['key' => 'breaking-news', 'title' => 'ब्रेकिंग न्यूज़', 'slugs' => ['breaking-news'], 'names' => ['ब्रेकिंग न्यूज़', 'ब्रेकिंग न्यूज़', 'Breaking News'], 'layout' => 'compact-list', 'limit' => 8, 'show_meta' => true, 'view_all' => true, 'fallback' => 'breaking-flag'],
        ['key' => 'politics', 'title' => 'राजनीति', 'slugs' => ['politics'], 'names' => ['राजनीति', 'Politics'], 'layout' => 'compact-list', 'limit' => 8, 'show_meta' => true, 'view_all' => true],
        ['key' => 'education', 'title' => 'एजुकेशन', 'slugs' => ['education'], 'names' => ['एजुकेशन', 'Education'], 'layout' => 'compact-list', 'limit' => 8, 'show_meta' => true, 'view_all' => true],
        ['key' => 'business', 'title' => 'बिजनेस', 'slugs' => ['business'], 'names' => ['बिजनेस', 'Business'], 'layout' => 'grid', 'limit' => 8, 'show_meta' => true, 'view_all' => true],
        ['key' => 'entertainment', 'title' => 'मनोरंजन', 'slugs' => ['entertainment'], 'names' => ['मनोरंजन', 'Entertainment'], 'layout' => 'grid', 'limit' => 8, 'show_meta' => true, 'view_all' => true],
    ],
];
