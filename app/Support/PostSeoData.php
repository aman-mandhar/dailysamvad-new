<?php

namespace App\Support;

class PostSeoData
{
    /** @return array<string, string> */
    public static function robotsOptions(): array
    {
        return [
            'index_follow' => 'Index, Follow',
            'noindex_follow' => 'No Index, Follow',
            'index_nofollow' => 'Index, No Follow',
            'noindex_nofollow' => 'No Index, No Follow',
        ];
    }

    /** @param array<string, mixed>|null $seoData */
    public static function robotsDirective(?array $seoData): ?string
    {
        $robots = $seoData['robots'] ?? null;

        if (! is_array($robots) || ! array_key_exists('index', $robots) || ! array_key_exists('follow', $robots)) {
            return null;
        }

        return match ([$robots['index'], $robots['follow']]) {
            [true, true] => 'index_follow',
            [false, true] => 'noindex_follow',
            [true, false] => 'index_nofollow',
            [false, false] => 'noindex_nofollow',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>|null  $seoData
     * @return array<string, mixed>|null
     */
    public static function mergeRobots(?array $seoData, ?string $directive): ?array
    {
        $seoData ??= [];

        if (blank($directive)) {
            unset($seoData['robots']);

            return $seoData === [] ? null : $seoData;
        }

        $seoData['robots'] = match ($directive) {
            'index_follow' => ['index' => true, 'follow' => true],
            'noindex_follow' => ['index' => false, 'follow' => true],
            'index_nofollow' => ['index' => true, 'follow' => false],
            'noindex_nofollow' => ['index' => false, 'follow' => false],
            default => null,
        };

        if ($seoData['robots'] === null) {
            unset($seoData['robots']);
        }

        return $seoData === [] ? null : $seoData;
    }
}
