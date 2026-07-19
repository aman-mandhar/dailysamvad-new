<?php

namespace Tests\Unit\SEO;

use App\SEO\SchemaGraph;
use JsonException;
use PHPUnit\Framework\TestCase;

class SchemaGraphTest extends TestCase
{
    /** @throws JsonException */
    public function test_json_serialization_preserves_unicode_and_neutralizes_script_content(): void
    {
        $graph = new SchemaGraph([[
            '@type' => 'WebPage',
            '@id' => 'https://example.com/#webpage',
            'name' => 'ਪੰਜਾਬ हिंदी "quote" & apostrophe\'s </script>',
        ]]);

        $json = $graph->toJson();
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertSame('ਪੰਜਾਬ हिंदी "quote" & apostrophe\'s </script>', $decoded['@graph'][0]['name']);
        $this->assertStringNotContainsString('</script>', $json);
        $this->assertStringContainsString('ਪੰਜਾਬ', $json);
        $this->assertStringContainsString('हिंदी', $json);
    }
}
