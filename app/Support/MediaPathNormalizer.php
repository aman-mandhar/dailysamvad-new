<?php

namespace App\Support;

use Illuminate\Support\Str;

class MediaPathNormalizer
{
    public function normalize(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || str_contains($value, "\0")) {
            return null;
        }

        $value = str_replace('\\', '/', $value);
        if (preg_match('/^[a-zA-Z]:\//', $value) === 1) {
            return null;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $value) === 1 && ! Str::startsWith($value, ['http://', 'https://'])) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $this->normalizeUrl($value);
        }

        $path = rawurldecode((string) (parse_url($value, PHP_URL_PATH) ?? $value));
        $path = preg_replace('#/+#', '/', $path) ?? $path;
        $path = ltrim($path, '/');

        foreach (['public/storage/', 'storage/'] as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        $wordpressMarker = 'wp-content/uploads/';
        if (str_contains($path, $wordpressMarker)) {
            $path = trim((string) config('media.wordpress_path', 'wordpress/uploads'), '/').'/'.Str::after($path, $wordpressMarker);
        }

        if ($path === '' || $this->containsTraversal($path) || $this->hasExecutableExtension($path)) {
            return null;
        }

        return $path;
    }

    public function hasExecutableExtension(string $path): bool
    {
        $extension = Str::lower(pathinfo((string) parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));

        return $extension !== '' && in_array($extension, (array) config('media.executable_extensions', []), true);
    }

    private function normalizeUrl(string $value): ?string
    {
        $parts = parse_url($value);
        if ($parts === false || ! isset($parts['scheme'], $parts['host']) || ! in_array(Str::lower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $path = rawurldecode($parts['path'] ?? '');
        if ($this->containsTraversal($path) || $this->hasExecutableExtension($path)) {
            return null;
        }

        if (str_contains($path, '/wp-content/uploads/')) {
            return $this->normalize($path);
        }

        return $value;
    }

    private function containsTraversal(string $path): bool
    {
        return collect(explode('/', $path))->contains('..');
    }
}
