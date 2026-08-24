<?php

$configured = static function (string $key, string $fallback): string {
    $value = env($key);

    return is_string($value) && trim($value) !== '' ? $value : $fallback;
};

return [
    'organization_name' => env('ORGANIZATION_NAME', 'Rzana Punjab'),
    'website_name' => env('WEBSITE_NAME', 'Rzana Punjab'),
    'address' => $configured('ORGANIZATION_ADDRESS', '92A, Rajiv Gandhi Vihar, Surya Enclave, Jalandhar (Punjab) - 144001'),
    'phone' => $configured('ORGANIZATION_PHONE', '+91 9888190945'),
    'email' => $configured('ORGANIZATION_EMAIL', 'mmmmediahouse@gmail.com'),
    'office_hours' => $configured('ORGANIZATION_OFFICE_HOURS', '10am - 8pm'),
    'chief_editor' => env('ORGANIZATION_CHIEF_EDITOR'),
    'social_links' => [
        'facebook' => $configured('ORGANIZATION_FACEBOOK_URL', 'https://www.facebook.com/dailysamvad'),
        'x' => env('ORGANIZATION_X_URL'),
        'instagram' => $configured('ORGANIZATION_INSTAGRAM_URL', 'https://www.instagram.com/dailysamvadnews'),
        'youtube' => $configured('ORGANIZATION_YOUTUBE_URL', 'https://www.youtube.com/@DailySamvad'),
        'whatsapp' => $configured('ORGANIZATION_WHATSAPP_URL', 'https://whatsapp.com/channel/0029VaNmS3h7dmefXnv8T71s'),
        'whatsapp_chat' => $configured('ORGANIZATION_WHATSAPP_CHAT_URL', 'https://chat.whatsapp.com/FqbcTOAQUSrBeI1BnZOxOP'),
    ],
];
