<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;
use RuntimeException;

class ImageOptimizationService
{
    public function __construct(private readonly FilesystemManager $filesystems) {}

    public function capabilities(): array
    {
        return ['gd' => extension_loaded('gd'), 'webp' => function_exists('imagewebp'), 'avif' => function_exists('imageavif')];
    }

    public function process(Media $media, bool $force = false): array
    {
        $disk = $this->disk($media);
        if (! $disk->exists($media->path) || ! $this->supported($media->mime_type) || ! function_exists('imagecreatefromstring')) return [];
        $bytes = $disk->get($media->path);
        $info = @getimagesizefromstring($bytes);
        if (! $info || ($info[0] * $info[1]) > (int) config('image_optimization.max_pixels')) throw new RuntimeException('Unsafe or oversized image');
        $source = @imagecreatefromstring($bytes);
        if (! $source) throw new RuntimeException('Unreadable image');
        $variants = (array) data_get($media->metadata, 'derivatives', []);
        foreach ((array) config('image_optimization.responsive_widths') as $width) {
            if ($width >= $info[0]) continue;
            foreach ($this->formats($media->mime_type) as $format) {
                $path = $this->path($media, $width, $format);
                $existing = collect($variants)->first(fn ($v) => ($v['path'] ?? null) === $path && filled($v['verified_at'] ?? null));
                if ($existing && ! $force && $disk->exists($path)) continue;
                $height = max(1, (int) round($info[1] * ($width / $info[0])));
                $target = imagecreatetruecolor($width, $height);
                if (in_array($media->mime_type, ['image/png', 'image/gif'], true)) { imagealphablending($target, false); imagesavealpha($target, true); $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127); imagefill($target, 0, 0, $transparent); }
                imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
                ob_start(); $ok = match ($format) { 'webp' => imagewebp($target, null, (int) config('image_optimization.quality.webp')), 'avif' => imageavif($target, null, (int) config('image_optimization.quality.avif')), default => imagejpeg($target, null, (int) config('image_optimization.quality.jpeg')) }; $encoded = ob_get_clean(); imagedestroy($target);
                if (! $ok || ! is_string($encoded)) continue;
                $disk->put($path, $encoded);
                $variants = array_values(array_filter($variants, fn ($v) => ($v['path'] ?? null) !== $path));
                $variants[] = ['path' => $path, 'width' => $width, 'height' => $height, 'format' => $format, 'bytes' => strlen($encoded), 'verified_at' => now()->toIso8601String()];
            }
        }
        imagedestroy($source);
        $media->forceFill(['metadata' => array_merge((array) $media->metadata, ['derivatives' => $variants, 'optimized_at' => now()->toIso8601String(), 'capabilities' => $this->capabilities()])])->saveQuietly();
        return $variants;
    }

    public function path(Media $media, int $width, string $format = 'webp'): string { return trim(config('image_optimization.derivative_path'), '/').'/'.config('image_optimization.version').'/'.$media->id.'/'.$width.'.'.$format; }
    private function disk(Media $media): Filesystem { return $this->filesystems->disk($media->disk ?: config('image_optimization.disk')); }
    private function supported(?string $mime): bool { return in_array($mime, ['image/jpeg', 'image/png'], true); }
    private function formats(?string $mime): array { $out = function_exists('imagewebp') ? ['webp'] : []; if (function_exists('imageavif') && config('image_optimization.formats.avif')) $out[] = 'avif'; return $out; }
}
