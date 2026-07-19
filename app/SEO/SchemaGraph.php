<?php

namespace App\SEO;

use JsonException;

final readonly class SchemaGraph
{
    /** @param array<int, array<string, mixed>> $nodes */
    public function __construct(public array $nodes) {}

    /** @throws JsonException */
    public function toJson(): string
    {
        return json_encode([
            '@context' => 'https://schema.org',
            '@graph' => $this->nodes,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}
