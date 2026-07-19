<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Support\Str;

class ResponsiveImageData
{
    public function __construct(private readonly MediaUrlResolver $urls) {}

    /** @return array{src: ?string, srcset: ?string, width: ?int, height: ?int} */
    public function for(?string $source, ?Media $media = null): array
    {
        $src = $this->urls->resolve($source, $media?->disk);
        if ($src === null || Str::startsWith($src, ['http://', 'https://'])) {
            return ['src' => $src, 'srcset' => null, 'width' => null, 'height' => null];
        }

        $candidates = collect((array) data_get($media?->metadata, 'derivatives', []))
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['verified_at'] ?? null) && (int) ($item['width'] ?? 0) > 0)
            ->mapWithKeys(function (array $item) use ($media): array {
                $url = $this->urls->resolve($item['path'] ?? null, $media?->disk);

                return $url ? [(int) $item['width'] => $url] : [];
            });

        if ($media?->width && $media->missing_at === null) {
            $candidates->put($media->width, $src);
        }

        $srcset = $candidates->sortKeys()->map(fn (string $url, int $width): string => "{$url} {$width}w")->implode(', ');

        return [
            'src' => $src,
            'srcset' => $srcset !== '' ? $srcset : null,
            'width' => $media?->width,
            'height' => $media?->height,
        ];
    }
}
