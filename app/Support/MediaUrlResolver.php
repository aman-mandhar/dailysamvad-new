<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;

class MediaUrlResolver
{
    public function __construct(
        private readonly MediaPathNormalizer $normalizer,
        private readonly FilesystemManager $filesystems,
    ) {}

    public function resolve(?string $value, ?string $disk = null): ?string
    {
        $normalized = $this->normalizer->normalize($value);
        if ($normalized === null || Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        $url = $this->filesystems->disk($disk ?: (string) config('media.disk', 'public'))->url($normalized);
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? '/'.ltrim(preg_replace('#/+#', '/', $path) ?? $path, '/') : $url;
    }

    public function resolveExisting(?string $value, ?string $disk = null): ?string
    {
        $normalized = $this->normalizer->normalize($value);
        if ($normalized === null || Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        $disk = $disk ?: (string) config('media.disk', 'public');

        return $this->filesystems->disk($disk)->exists($normalized) ? $this->resolve($normalized, $disk) : null;
    }
}
