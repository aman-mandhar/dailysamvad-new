<?php

namespace App\Services;

use App\Data\AdvertisementData;
use App\Data\ArticleContentBlockData;
use App\Support\TrustedArticleHtml;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
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
        $clean = (string) $this->sanitizer->sanitize($html);

        if ($clean === '') {
            return collect();
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
            if ($node instanceof DOMElement && in_array(strtolower($node->tagName), ['p', 'h2', 'h3', 'h4', 'h5', 'h6'], true)) {
                $eligibleCount++;
                foreach ($insertions[$eligibleCount] ?? [] as $advertisement) {
                    $blocks->push(ArticleContentBlockData::advertisement($advertisement));
                }
                unset($insertions[$eligibleCount]);
            }
        }

        foreach ($insertions as $pending) {
            foreach ($pending as $advertisement) {
                $blocks->push(ArticleContentBlockData::advertisement($advertisement));
            }
        }

        return $blocks;
    }

    private function serializeNode(DOMDocument $document, DOMNode $node): string
    {
        $html = $document->saveHTML($node) ?: '';

        return $node instanceof DOMElement && strtolower($node->tagName) === 'table'
            ? '<div class="ds-article-table" role="region" aria-label="Scrollable table" tabindex="0">'.$html.'</div>'
            : $html;
    }
}
