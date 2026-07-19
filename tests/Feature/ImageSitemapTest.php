<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImageSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_sitemap_reuses_safe_social_image_resolution_and_metadata(): void
    {
        config(['seo.sitemaps.cache_ttl' => 0]);
        $media = Media::query()->create([
            'disk' => 'public', 'path' => 'wordpress/uploads/ਪੰਜਾਬ image.webp', 'mime_type' => 'image/webp',
            'width' => 1200, 'height' => 675, 'alt_text' => '<b>ਪੰਜਾਬ caption</b>', 'caption' => '<p>Clean & ਪੰਜਾਬ</p><script>bad()</script>',
        ]);
        $relative = Post::factory()->published()->create([
            'title' => 'Image article', 'featured_media_id' => $media->id, 'featured_image' => 'wordpress/uploads/ਪੰਜਾਬ image.webp',
        ]);
        $absolute = Post::factory()->published()->create(['featured_image' => 'https://cdn.example.com/photo.jpg']);
        Post::factory()->published()->create(['featured_image' => 'C:\\private\\photo.jpg']);
        Post::factory()->published()->create(['featured_image' => 'javascript:alert(1)']);
        $draft = Post::factory()->create(['featured_image' => 'https://cdn.example.com/private.jpg']);

        $content = $this->get('/image-sitemap.xml')->assertOk()->streamedContent();
        $document = new DOMDocument;
        $this->assertTrue($document->loadXML($content, LIBXML_NONET));
        $images = $document->getElementsByTagNameNS('http://www.google.com/schemas/sitemap-image/1.1', 'loc');
        $locations = array_map(fn ($node): string => $node->textContent, iterator_to_array($images));

        $this->assertContains('http://localhost/storage/wordpress/uploads/%E0%A8%AA%E0%A9%B0%E0%A8%9C%E0%A8%BE%E0%A8%AC%20image.webp', $locations);
        $this->assertContains('https://cdn.example.com/photo.jpg', $locations);
        $this->assertStringNotContainsString('C:\\', $content);
        $this->assertStringNotContainsString('javascript:', $content);
        $this->assertStringNotContainsString($draft->publicUrl(), $content);
        $this->assertStringContainsString($relative->publicUrl(), $content);
        $this->assertSame('Clean & ਪੰਜਾਬ', $document->getElementsByTagNameNS('http://www.google.com/schemas/sitemap-image/1.1', 'caption')->item(0)->textContent);
        $this->assertStringNotContainsString('bad()', $content);
        $this->assertSame(count($locations), count(array_unique($locations)));
    }
}
