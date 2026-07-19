<?php

namespace App\SEO\Sitemap;

use Illuminate\Support\Str;

class SitemapUrlValidator
{
    public function validate(mixed $value): ?string
    {
        if (! is_string($value) || blank($value) || str_contains($value, '\\') || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return null;
        }
        $value = str_replace(' ', '%20', trim($value));
        if (! filter_var($value, FILTER_VALIDATE_URL) || ! in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return null;
        }
        if (Str::lower((string) parse_url($value, PHP_URL_HOST)) !== Str::lower((string) parse_url(route('home'), PHP_URL_HOST))) {
            return null;
        }
        if (app()->environment('production') && Str::startsWith($value, 'http://')) {
            $value = 'https://'.Str::after($value, 'http://');
        }

        $parts = parse_url($value);
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port.($parts['path'] ?? '').$query;
    }
}
