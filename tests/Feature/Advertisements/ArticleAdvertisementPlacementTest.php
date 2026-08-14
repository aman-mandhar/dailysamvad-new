<?php

namespace Tests\Feature\Advertisements;

use App\Data\AdvertisementData;
use App\Enums\AdvertisementPosition;
use App\Services\ArticleContentComposer;
use App\Support\TrustedArticleHtml;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ArticleAdvertisementPlacementTest extends TestCase
{
    #[DataProvider('paragraphCounts')]
    public function test_exact_paragraph_fallback_matrix(int $paragraphs): void
    {
        $positions = collect(AdvertisementPosition::paragraphPositions())->mapWithKeys(fn ($position, $index) => [$position->value => $index + 1])->all();
        $ads = collect(AdvertisementPosition::bottomPositions())->mapWithKeys(fn ($position) => [$position->value => $this->ad($position->value)])->all();
        $html = collect(range(1, $paragraphs))->map(fn ($number) => "<p>Paragraph $number</p>")->implode('');
        if ($paragraphs === 0) {
            $html = '<h2>Heading only</h2>';
        }

        $blocks = (new ArticleContentComposer(new TrustedArticleHtml))->compose($html, $ads, $positions);
        $stack = $blocks->firstWhere('type', 'advertisement_bottom_stack')?->advertisements?->pluck('slot')->all() ?? [];
        $expectedStack = collect(AdvertisementPosition::bottomPositions())->pluck('value')->all();

        $this->assertSame([], $blocks->where('type', 'advertisement')->all());
        $this->assertSame($expectedStack, $stack);
        $this->assertSame(count($expectedStack), count(array_unique($stack)));
        $this->assertSame('advertisement_bottom_stack', $blocks->last()->type);
    }

    public function test_google_ads_use_paragraph_slots_while_third_party_ads_render_at_the_bottom(): void
    {
        $positions = collect(AdvertisementPosition::paragraphPositions())->mapWithKeys(fn ($position, $index) => [$position->value => $index + 1])->all();
        $ads = [
            'ARTICLE_AFTER_PARAGRAPH_1' => $this->ad('GOOGLE_1', type: 'provider_code'),
            'ARTICLE_AFTER_PARAGRAPH_3' => $this->ad('GOOGLE_3', type: 'provider_code'),
            'ARTICLE_BOTTOM_1' => $this->ad('BANNER_1', type: 'image'),
            'ARTICLE_BOTTOM_2' => $this->ad('VIDEO_1', type: 'video'),
            'ARTICLE_BOTTOM_3' => $this->ad('BANNER_2', type: 'html'),
            'ARTICLE_BOTTOM_4' => $this->ad('BOTTOM_VIDEO', type: 'video'),
        ];
        $html = collect(range(1, 5))->map(fn ($number) => "<p>Paragraph $number</p>")->implode('');

        $blocks = (new ArticleContentComposer(new TrustedArticleHtml))->compose($html, $ads, $positions);

        $this->assertSame(['GOOGLE_1', 'GOOGLE_3'], $blocks->where('type', 'advertisement')->pluck('advertisement.slot')->values()->all());
        $this->assertSame(['BANNER_1', 'VIDEO_1', 'BANNER_2', 'BOTTOM_VIDEO'], $blocks->firstWhere('type', 'advertisement_bottom_stack')->advertisements->pluck('slot')->all());
        $this->assertSame('advertisement_bottom_stack', $blocks->last()->type);
    }

    public static function paragraphCounts(): array
    {
        return ['zero' => [0], 'one' => [1], 'two' => [2], 'three' => [3], 'four' => [4], 'five' => [5], 'more' => [7]];
    }

    public function test_inactive_ads_create_no_stack_gap_and_css_is_exactly_five_pixels(): void
    {
        $blocks = (new ArticleContentComposer(new TrustedArticleHtml))->compose('<p>One</p>', [
            'ARTICLE_BOTTOM_1' => $this->ad('ARTICLE_BOTTOM_1'),
            'ARTICLE_BOTTOM_2' => $this->ad('ARTICLE_BOTTOM_2', false),
            'ARTICLE_BOTTOM_3' => $this->ad('ARTICLE_BOTTOM_3'),
        ], ['ARTICLE_AFTER_PARAGRAPH_1' => 1, 'ARTICLE_AFTER_PARAGRAPH_2' => 2]);
        $rendered = Blade::render('<x-news.article.content :blocks="$blocks" />', compact('blocks'));
        $this->assertStringContainsString('article-ad-bottom-stack', $rendered);
        $this->assertStringNotContainsString('ARTICLE_BOTTOM_2', $rendered);
        $this->assertStringContainsString('gap: 5px', file_get_contents(resource_path('css/frontend/article.css')));
    }

    private function ad(string $slot, bool $enabled = true, string $type = 'placeholder'): AdvertisementData
    {
        return new AdvertisementData($slot, $enabled, $type, 'Advertisement', null, null, null, '', 728, 90, true, 'sponsored');
    }
}
