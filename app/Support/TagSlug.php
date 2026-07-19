<?php

namespace App\Support;

use Illuminate\Support\Str;

class TagSlug
{
    public static function fromName(?string $name): string
    {
        $name = Str::squish((string) $name);
        $asciiSlug = Str::slug($name);

        if ($asciiSlug !== '') {
            return $asciiSlug;
        }

        return Str::lower(trim((string) preg_replace('/[^\pL\pM\pN]+/u', '-', $name), '-'));
    }
}
