<?php

namespace App\Support;

use Illuminate\Support\Str;

final class CacheKey
{
    public function make(string $domain, string $resource, string $scope = 'default', string|int|null $identifier = null, string $variant = 'default', ?string $version = null, array $parameters = []): string
    {
        ksort($parameters);
        $parts = [$domain, $resource, $scope];
        if ($identifier !== null) $parts[] = (string) $identifier;
        if ($variant !== 'default') $parts[] = $variant;
        $parts[] = $version ?? config('cache_architecture.version', 'v1');
        if ($parameters !== []) $parts[] = hash('xxh3', json_encode($this->normalize($parameters), JSON_THROW_ON_ERROR));
        $prefix = trim((string) config('cache.prefix', Str::slug((string) config('app.name')).'-'), ':').':'.Str::slug((string) config('app.env')).':';
        return $prefix.':'.implode(':', array_map(fn (string $part): string => Str::slug($part, '_'), $parts));
    }

    private function normalize(mixed $value): mixed
    {
        if (is_array($value)) { ksort($value); return array_map(fn (mixed $item): mixed => $this->normalize($item), $value); }
        return is_scalar($value) || $value === null ? $value : (string) $value;
    }
}
