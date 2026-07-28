<?php

namespace Tests\Unit;

use App\Data\AdvertisementData;
use App\Services\ArticleContentComposer;
use App\Support\TrustedArticleHtml;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ArticleContentComposerTest extends TestCase
{
    #[DataProvider('safeContent')]
    public function test_it_preserves_supported_content(string $html, string $expected): void
    {
        $rendered = $this->composer()->compose($html)->map(fn ($block) => (string) $block->html)->implode('');

        $this->assertStringContainsString($expected, $rendered);
    }

    /** @return array<string, array{string, string}> */
    public static function safeContent(): array
    {
        return [
            'paragraph' => ['<p>Hindi खबर</p>', '<p>Hindi खबर</p>'],
            'list' => ['<ul><li>One</li></ul>', '<ul><li>One</li></ul>'],
            'blockquote' => ['<blockquote>Quote</blockquote>', '<blockquote>Quote</blockquote>'],
            'table wrapper' => ['<table><tr><td>Cell</td></tr></table>', 'class="ds-article-table"'],
            'youtube' => ['<iframe src="https://www.youtube.com/embed/abc"></iframe>', 'youtube.com/embed/abc'],
            'shortcode text' => ['<p>[gallery ids="1,2"]</p>', '[gallery ids="1,2"]'],
        ];
    }

    public function test_ads_are_inserted_only_between_top_level_blocks(): void
    {
        $blocks = $this->composer()->compose(
            '<ul><li><p>Nested</p></li></ul><p>One</p><table><tr><td>Cell</td></tr></table><p>Two</p>',
            ['ARTICLE_INLINE_1' => $this->advertisement()],
            ['ARTICLE_INLINE_1' => 1],
        );

        $this->assertSame(['html', 'html', 'advertisement', 'html', 'html'], $blocks->pluck('type')->all());
        $this->assertStringContainsString('<p>One</p>', (string) $blocks[1]->html);
        $this->assertStringContainsString('<table>', (string) $blocks[3]->html);
    }

    public function test_short_content_places_enabled_pending_ads_at_the_end_and_omits_disabled_ads(): void
    {
        $blocks = $this->composer()->compose(
            '<p>Only paragraph</p>',
            ['one' => $this->advertisement(), 'two' => $this->advertisement(false)],
            ['one' => 3, 'two' => 7],
        );

        $this->assertSame(['html', 'advertisement'], $blocks->pluck('type')->all());
    }

    public function test_empty_and_malformed_content_do_not_crash(): void
    {
        $this->assertCount(0, $this->composer()->compose(''));
        $this->assertNotEmpty($this->composer()->compose('<p>Open <strong>markup'));
    }

    public function test_newsroom_embed_blocks_are_rendered_and_untrusted_hosts_are_rejected(): void
    {
        $youtube = '<div data-type="customBlock" data-id="youtube-video" data-config='.
            "'{\"url\":\"https://www.youtube.com/watch?v=dQw4w9WgXcQ\",\"title\":\"News video\"}'></div>";
        $xPost = '<div data-type="customBlock" data-id="x-post" data-config='.
            "'{\"url\":\"https://x.com/dailysamvad/status/1234567890\"}'></div>";

        $rendered = $this->composer()->compose($youtube.$xPost)->map(fn ($block) => (string) $block->html)->implode('');

        $this->assertStringContainsString('youtube-nocookie.com/embed/dQw4w9WgXcQ', $rendered);
        $this->assertStringContainsString('platform.twitter.com/embed/Tweet.html?id=1234567890', $rendered);
        $this->assertStringNotContainsString('<script', $rendered);
    }

    public function test_editor_alignment_and_color_styles_are_safely_preserved(): void
    {
        $rendered = $this->composer()->compose(
            '<p style="text-align: justify; position: fixed"><span class="color" style="--color: #dc2626; background-image: url(javascript:alert(1))">Text</span><mark>Marked</mark></p>',
        )->map(fn ($block) => (string) $block->html)->implode('');

        $this->assertStringContainsString('text-align: justify', $rendered);
        $this->assertStringContainsString('--color: #dc2626', $rendered);
        $this->assertStringContainsString('<mark>Marked</mark>', $rendered);
        $this->assertStringNotContainsString('position:', $rendered);
        $this->assertStringNotContainsString('javascript:', $rendered);
    }

    private function composer(): ArticleContentComposer
    {
        return new ArticleContentComposer(new TrustedArticleHtml);
    }

    private function advertisement(bool $enabled = true): AdvertisementData
    {
        return new AdvertisementData('TEST', $enabled, 'placeholder', 'Advertisement', null, null, null, '', 728, 90, true, 'sponsored');
    }
}
