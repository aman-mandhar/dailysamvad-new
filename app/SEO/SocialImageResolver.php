<?php

namespace App\SEO;

use App\Models\Media;
use App\Support\MediaPathNormalizer;
use App\Support\MediaUrlResolver;
use Illuminate\Support\Str;

class SocialImageResolver
{
    public function __construct(
        private readonly MediaPathNormalizer $paths,
        private readonly MediaUrlResolver $urls,
    ) {}

    public function resolve(?string $source, ?string $alt, ?Media $media = null): ?SocialImage
    {
        if ($media?->missing_at !== null) {
            return null;
        }

        $url = $this->absoluteUrl($source, $media?->disk);
        if ($url === null) {
            return null;
        }

        $mime = $this->mimeType($media?->mime_type, $url);
        $width = $media?->width;
        $height = $media?->height;

        return new SocialImage(
            url: $url,
            alt: $this->text($media?->alt_text, $alt, config('organization.website_name')),
            mimeType: $mime,
            width: $width,
            height: $height,
            large: $width !== null && $height !== null ? $width >= 600 && $height >= 315 : true,
        );
    }

    public function configuredDefault(?string $alt = null): ?SocialImage
    {
        $source = config('seo.default_social_image') ?: config('seo.site_logo');
        $url = $this->absoluteUrl($source);
        if ($url === null) {
            return null;
        }

        $width = filter_var(config('seo.default_social_image_width'), FILTER_VALIDATE_INT) ?: null;
        $height = filter_var(config('seo.default_social_image_height'), FILTER_VALIDATE_INT) ?: null;

        return new SocialImage(
            url: $url,
            alt: $this->text($alt, config('organization.website_name')),
            mimeType: $this->mimeType(config('seo.default_social_image_mime'), $url),
            width: $width,
            height: $height,
            large: $width !== null && $height !== null && $width >= 600 && $height >= 315,
        );
    }

    private function absoluteUrl(mixed $source, ?string $disk = null): ?string
    {
        if (! is_string($source) || blank($source) || preg_match('/[\x00-\x1F\x7F]/', $source)) {
            return null;
        }
        $source = trim($source);
        if (str_contains($source, '\\') || preg_match('/^(?:[a-z]:|\/home\/|\/var\/|\/private\/)/i', $source)) {
            return null;
        }

        if (Str::startsWith($source, ['http://', 'https://'])) {
            $normalized = $this->paths->normalize($source);
            $url = Str::startsWith((string) $normalized, ['http://', 'https://']) ? $normalized : $this->urls->resolve($normalized, $disk);
        } elseif (Str::startsWith($source, ['/images/', '/build/', '/favicon', 'images/', 'build/', 'favicon'])) {
            $url = url($source);
        } else {
            $url = $this->urls->resolve($source, $disk);
            $url = $url ? url($url) : null;
        }

        $url = is_string($url) ? $this->encodeUrl($url) : $url;
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return null;
        }

        if (app()->environment('production') && Str::startsWith($url, 'http://')) {
            $url = 'https://'.Str::after($url, 'http://');
        }

        return $url;
    }

    private function encodeUrl(string $url): string
    {
        $parts = parse_url(str_replace(' ', '%20', $url));
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }
        $path = implode('/', array_map(
            static fn (string $segment): string => rawurlencode(rawurldecode($segment)),
            explode('/', $parts['path'] ?? ''),
        ));
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port.$path.$query;
    }

    private function mimeType(mixed $known, string $url): ?string
    {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (is_string($known) && in_array(Str::lower($known), $allowed, true)) {
            return Str::lower($known);
        }

        return match (Str::lower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif',
            default => null,
        };
    }

    private function text(mixed ...$values): string
    {
        $value = collect($values)->first(fn (mixed $item): bool => is_string($item) && filled($item)) ?? '';

        return Str::squish(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
