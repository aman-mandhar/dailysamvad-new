<?php

namespace App\SEO;

final readonly class TwitterCardData
{
    public function __construct(
        public string $card,
        public string $title,
        public string $description,
        public ?SocialImage $image = null,
        public ?string $site = null,
        public ?string $creator = null,
    ) {}
}
