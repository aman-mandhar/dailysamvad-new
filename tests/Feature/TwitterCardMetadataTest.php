<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwitterCardMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_reuses_open_graph_values_and_normalizes_twitter_handles(): void
    {
        config(['seo.twitter_site' => 'https://x.com/DailySamvad']);
        $author = User::factory()->create(['x_url' => '@news_reporter']);
        $post = Post::factory()->published()->create([
            'author_id' => $author->id,
            'title' => 'Twitter headline',
            'meta_description' => 'Twitter description.',
            'featured_image' => 'https://cdn.example.com/social.jpg',
            'featured_image_alt' => 'Twitter image alt',
        ]);

        $html = $this->get($post->publicUrl())->assertOk()->getContent();

        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
        $this->assertStringContainsString('name="twitter:title" content="Twitter headline"', $html);
        $this->assertStringContainsString('name="twitter:description" content="Twitter description."', $html);
        $this->assertStringContainsString('name="twitter:image" content="https://cdn.example.com/social.jpg"', $html);
        $this->assertStringContainsString('name="twitter:image:alt" content="Twitter image alt"', $html);
        $this->assertStringContainsString('name="twitter:site" content="@DailySamvad"', $html);
        $this->assertStringContainsString('name="twitter:creator" content="@news_reporter"', $html);
    }

    public function test_invalid_optional_handles_are_omitted_without_empty_tags(): void
    {
        config(['seo.twitter_site' => 'https://example.com/not-twitter']);
        $post = Post::factory()->published()->create();

        $html = $this->get($post->publicUrl())->assertOk()->getContent();

        $this->assertStringNotContainsString('name="twitter:site"', $html);
        $this->assertStringNotContainsString('name="twitter:creator"', $html);
        $this->assertStringNotContainsString('content=""', $html);
    }

    public function test_card_falls_back_to_summary_when_no_social_image_is_configured(): void
    {
        config(['seo.default_social_image' => null, 'seo.site_logo' => null]);
        $post = Post::factory()->published()->create(['featured_image' => null]);

        $html = $this->get($post->publicUrl())->assertOk()->getContent();

        $this->assertStringContainsString('name="twitter:card" content="summary"', $html);
        $this->assertStringNotContainsString('name="twitter:image"', $html);
        $this->assertStringNotContainsString('property="og:image"', $html);
    }
}
