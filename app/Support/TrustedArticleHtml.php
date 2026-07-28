<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\HtmlString;

class TrustedArticleHtml
{
    /** @var array<int, string> */
    private const ALLOWED_ELEMENTS = [
        'a', 'b', 'blockquote', 'br', 'code', 'div', 'em', 'figcaption', 'figure', 'h2', 'h3', 'h4', 'h5', 'h6',
        'hr', 'i', 'iframe', 'img', 'li', 'ol', 'p', 'pre', 'source', 'span', 'strong', 'table', 'tbody', 'td', 'th',
        'thead', 'tr', 'u', 'ul', 'video', 'mark',
    ];

    /** @var array<int, string> */
    private const REMOVED_WITH_CONTENT = ['applet', 'embed', 'noscript', 'object', 'script', 'style', 'svg'];

    /** @var array<string, array<int, string>> */
    private const ELEMENT_ATTRIBUTES = [
        'a' => ['href', 'rel', 'target', 'title'],
        'img' => ['alt', 'height', 'loading', 'src', 'title', 'width'],
        'iframe' => ['allow', 'allowfullscreen', 'height', 'loading', 'src', 'title', 'width'],
        'source' => ['src', 'type'],
        'video' => ['controls', 'height', 'poster', 'preload', 'width'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan', 'scope'],
    ];

    public function sanitize(string $html): HtmlString
    {
        if (! class_exists(DOMDocument::class)) {
            return new HtmlString(nl2br(e(strip_tags($html))));
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="trusted-article-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $root = (new DOMXPath($document))->query('//*[@id="trusted-article-root"]')->item(0);

        if (! $root instanceof DOMElement) {
            return new HtmlString('');
        }

        $this->sanitizeChildren($root);

        $clean = '';

        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return new HtmlString($clean);
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        $children = iterator_to_array($parent->childNodes);

        foreach ($children as $child) {
            if ($child instanceof DOMComment) {
                $parent->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $element = strtolower($child->tagName);

            if (in_array($element, self::REMOVED_WITH_CONTENT, true)) {
                $parent->removeChild($child);

                continue;
            }

            $this->sanitizeChildren($child);

            if (! in_array($element, self::ALLOWED_ELEMENTS, true)) {
                while ($child->firstChild !== null) {
                    $parent->insertBefore($child->firstChild, $child);
                }

                $parent->removeChild($child);

                continue;
            }

            $this->sanitizeAttributes($child, $element);
        }
    }

    private function sanitizeAttributes(DOMElement $node, string $element): void
    {
        $allowed = [...(self::ELEMENT_ATTRIBUTES[$element] ?? []), 'class'];
        if (in_array($element, ['div', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span'], true)) {
            $allowed[] = 'style';
        }
        $attributes = iterator_to_array($node->attributes);

        foreach ($attributes as $attribute) {
            $name = strtolower($attribute->name);

            if (! in_array($name, $allowed, true)
                || (in_array($name, ['href', 'src'], true) && ! $this->isSafeUrl($attribute->value, $name === 'src'))) {
                $node->removeAttribute($attribute->name);
            }
        }

        if ($node->hasAttribute('style')) {
            $style = $this->sanitizeStyle($node->getAttribute('style'));
            $style === '' ? $node->removeAttribute('style') : $node->setAttribute('style', $style);
        }

        if ($element === 'a' && $node->getAttribute('target') === '_blank') {
            $node->setAttribute('rel', 'noopener noreferrer');
        } elseif ($element === 'a' && filled($node->getAttribute('target')) && $node->getAttribute('target') !== '_self') {
            $node->removeAttribute('target');
        }

        if ($element === 'img' && filled($node->getAttribute('src'))) {
            $node->setAttribute('loading', 'lazy');
        }

        if ($element === 'iframe') {
            if (! $this->isTrustedEmbedUrl($node->getAttribute('src'))) {
                $node->parentNode?->removeChild($node);

                return;
            }

            $node->setAttribute('loading', 'lazy');
            if (blank($node->getAttribute('title'))) {
                $node->setAttribute('title', 'Embedded media');
            }
        }
    }

    private function isSafeUrl(string $url, bool $image): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme === null) {
            return ! str_starts_with($url, '//');
        }

        return in_array(strtolower($scheme), $image ? ['http', 'https'] : ['http', 'https', 'mailto'], true);
    }

    private function isTrustedEmbedUrl(string $url): bool
    {
        if (! $this->isSafeUrl($url, true)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return collect(['youtube.com', 'youtube-nocookie.com', 'youtu.be', 'drive.google.com', 'platform.twitter.com', 'facebook.com', 'twitter.com', 'x.com'])
            ->contains(fn (string $allowed): bool => $host === $allowed || str_ends_with($host, '.'.$allowed));
    }

    private function sanitizeStyle(string $style): string
    {
        $safe = [];

        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_pad(array_map('trim', explode(':', $declaration, 2)), 2, '');
            $property = strtolower($property);

            if ($property === 'text-align' && in_array(strtolower($value), ['left', 'right', 'center', 'justify', 'start', 'end'], true)) {
                $safe[] = 'text-align: '.strtolower($value);
            }

            if (in_array($property, ['--color', '--dark-color'], true)
                && preg_match('/^[\sA-Za-z0-9.,%\/+\-#_()]+$/', $value) === 1) {
                $safe[] = $property.': '.$value;
            }
        }

        return implode('; ', $safe);
    }
}
