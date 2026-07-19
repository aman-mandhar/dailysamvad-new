<?php

namespace App\SEO;

final readonly class SocialImage
{
    public function __construct(
        public string $url,
        public string $alt,
        public ?string $mimeType = null,
        public ?int $width = null,
        public ?int $height = null,
        public bool $large = false,
    ) {}
}
