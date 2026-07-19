<?php

namespace App\SEO;

final readonly class OpenGraphData
{
    /** @param array<int, string> $tags */
    public function __construct(
        public string $title,
        public string $description,
        public string $url,
        public string $type,
        public string $siteName,
        public string $locale,
        public ?SocialImage $image = null,
        public ?string $publishedTime = null,
        public ?string $modifiedTime = null,
        public ?string $authorUrl = null,
        public ?string $publisherUrl = null,
        public ?string $section = null,
        public array $tags = [],
    ) {}
}
