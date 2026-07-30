<?php

namespace App\Support;

final class AdvertisementUrl
{
    public static function normalize(?string $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }
        $url = trim($url);
        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return null;
        }
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true) ? $url : null;
    }
}
