<?php

namespace App\SEO;

final readonly class SEOData
{
    /** @param array<int, string> $keywords */
    public function __construct(
        public string $title,
        public string $description,
        public array $keywords,
        public string $author,
        public string $robots,
        public string $canonical,
        public OpenGraphData $openGraph,
        public TwitterCardData $twitter,
        public SchemaGraph $schema,
    ) {}
}
