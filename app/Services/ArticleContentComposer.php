<?php

namespace App\Services;

use App\Data\AdvertisementData;
use App\Data\ArticleContentBlockData;
use App\Filament\RichContent\ArticleRichContent;
use App\Support\TrustedArticleHtml;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Collection;

class ArticleContentComposer
{
    public function __construct(private readonly TrustedArticleHtml $sanitizer) {}

    /**
     * @param  array<string, AdvertisementData>  $advertisements
     * @param  array<string, int>  $positions
     * @return Collection<int, ArticleContentBlockData>
     */
    public function compose(string $html, array $advertisements = [], array $positions = []): Collection
    {
        if (str_contains($html, 'data-type="customBlock"')) {
            $html = RichContentRenderer::make($html)
                ->customBlocks(ArticleRichContent::blocks())
                ->toUnsafeHtml();
        }

        $clean = (string) $this->sanitizer->sanitize($html);

        if ($clean === '') {
            return $this->bottomStack($advertisements, $positions);
        }

        if (! class_exists(DOMDocument::class)) {
            return collect([ArticleContentBlockData::html($clean)])
                ->concat($this->bottomStack($advertisements, $positions));
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="article-compose-root">'.$clean.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        $root = (new DOMXPath($document))->query('//*[@id="article-compose-root"]')->item(0);

        if (! $root instanceof DOMElement) {
            return collect([ArticleContentBlockData::html($clean)])
                ->concat($this->bottomStack($advertisements, $positions));
        }

        $blocks = collect();

        foreach (iterator_to_array($root->childNodes) as $node) {
            $serialized = $this->serializeNode($document, $node);
            if ($serialized === '') {
                continue;
            }

            $blocks->push(ArticleContentBlockData::html($serialized));
        }

        return $blocks->concat($this->bottomStack($advertisements, $positions));
    }

    /** @param array<string, AdvertisementData> $advertisements @param array<string, int> $positions */
    private function bottomStack(array $advertisements, array $positions): Collection
    {
        $stack = collect($positions)->sort()->keys()->map(fn (string $slot) => $advertisements[$slot] ?? null)->filter(fn ($ad) => $ad?->enabled);
        if (($advertisements['ARTICLE_BOTTOM'] ?? null)?->enabled) {
            $stack->push($advertisements['ARTICLE_BOTTOM']);
        }

        return $stack->isEmpty() ? collect() : collect([ArticleContentBlockData::bottomStack($stack->values())]);
    }

    private function serializeNode(DOMDocument $document, DOMNode $node): string
    {
        $html = $document->saveHTML($node) ?: '';

        return $node instanceof DOMElement && strtolower($node->tagName) === 'table'
            ? '<div class="ds-article-table" role="region" aria-label="Scrollable table" tabindex="0">'.$html.'</div>'
            : $html;
    }
}
