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
            return $this->bottomStack($advertisements, 0, $positions);
        }

        if (! class_exists(DOMDocument::class)) {
            return collect([ArticleContentBlockData::html($clean)]);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="article-compose-root">'.$clean.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        $root = (new DOMXPath($document))->query('//*[@id="article-compose-root"]')->item(0);

        if (! $root instanceof DOMElement) {
            return collect([ArticleContentBlockData::html($clean)]);
        }

        $insertions = [];
        foreach ($positions as $slot => $position) {
            $advertisement = $advertisements[$slot] ?? null;
            if ($advertisement?->enabled) {
                $insertions[max(1, $position)][] = $advertisement;
            }
        }

        $blocks = collect();
        $eligibleCount = 0;

        foreach (iterator_to_array($root->childNodes) as $node) {
            $serialized = $this->serializeNode($document, $node);
            if ($serialized === '') {
                continue;
            }

            $blocks->push(ArticleContentBlockData::html($serialized));
            if ($this->isRenderableParagraph($node)) {
                $eligibleCount++;
                foreach ($insertions[$eligibleCount] ?? [] as $advertisement) {
                    $blocks->push(ArticleContentBlockData::advertisement($advertisement));
                }
                unset($insertions[$eligibleCount]);
            }
        }

        $fallback = collect($insertions)->sortKeys()->flatten()->filter(fn (AdvertisementData $ad) => $ad->enabled);
        $bottom = $advertisements['ARTICLE_BOTTOM'] ?? null;
        if ($bottom?->enabled) {
            $fallback->push($bottom);
        }
        if ($fallback->isNotEmpty()) {
            if ($this->usesCanonicalFallback($positions, $advertisements)) {
                $blocks->push(ArticleContentBlockData::bottomStack($fallback->values()));
            } else {
                $fallback->each(fn (AdvertisementData $advertisement) => $blocks->push(ArticleContentBlockData::advertisement($advertisement)));
            }
        }

        return $blocks;
    }

    private function isRenderableParagraph(DOMNode $node): bool
    {
        if (! $node instanceof DOMElement || strtolower($node->tagName) !== 'p' || $node->hasAttribute('hidden')) {
            return false;
        }
        $style = strtolower($node->getAttribute('style'));

        return ! str_contains($style, 'display:none') && ! str_contains($style, 'visibility:hidden') && trim($node->textContent) !== '';
    }

    /** @param array<string, AdvertisementData> $advertisements @param array<string, int> $positions */
    private function bottomStack(array $advertisements, int $count, array $positions): Collection
    {
        $stack = collect($positions)->sort()->keys()->map(fn (string $slot) => $advertisements[$slot] ?? null)->filter(fn ($ad) => $ad?->enabled);
        if (($advertisements['ARTICLE_BOTTOM'] ?? null)?->enabled) {
            $stack->push($advertisements['ARTICLE_BOTTOM']);
        }

        return $stack->isEmpty() ? collect() : collect([ArticleContentBlockData::bottomStack($stack->values())]);
    }

    /** @param array<string, int> $positions @param array<string, AdvertisementData> $advertisements */
    private function usesCanonicalFallback(array $positions, array $advertisements): bool
    {
        return array_key_exists('ARTICLE_BOTTOM', $advertisements)
            || collect(array_keys($positions))->contains(fn (string $slot) => str_starts_with($slot, 'ARTICLE_AFTER_PARAGRAPH_'));
    }

    private function serializeNode(DOMDocument $document, DOMNode $node): string
    {
        $html = $document->saveHTML($node) ?: '';

        return $node instanceof DOMElement && strtolower($node->tagName) === 'table'
            ? '<div class="ds-article-table" role="region" aria-label="Scrollable table" tabindex="0">'.$html.'</div>'
            : $html;
    }
}
