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

    private function composer(): ArticleContentComposer
    {
        return new ArticleContentComposer(new TrustedArticleHtml);
    }

    private function advertisement(bool $enabled = true): AdvertisementData
    {
        return new AdvertisementData('TEST', $enabled, 'placeholder', 'Advertisement', null, null, null, '', 728, 90, true, 'sponsored');
    }
}
