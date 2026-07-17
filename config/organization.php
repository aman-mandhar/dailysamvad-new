<?php

return [
    'organization_name' => env('ORGANIZATION_NAME', 'Daily Samvad'),
    'website_name' => env('WEBSITE_NAME', 'Daily Samvad'),
    'address' => env('ORGANIZATION_ADDRESS'),
    'phone' => env('ORGANIZATION_PHONE'),
    'email' => env('ORGANIZATION_EMAIL'),
    'office_hours' => env('ORGANIZATION_OFFICE_HOURS'),
    'chief_editor' => env('ORGANIZATION_CHIEF_EDITOR'),
    'social_links' => [
        'facebook' => env('ORGANIZATION_FACEBOOK_URL'),
        'x' => env('ORGANIZATION_X_URL'),
        'instagram' => env('ORGANIZATION_INSTAGRAM_URL'),
        'youtube' => env('ORGANIZATION_YOUTUBE_URL'),
    ],
];
