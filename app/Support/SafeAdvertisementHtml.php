<?php

namespace App\Support;

use DOMDocument;
use DOMElement;

final class SafeAdvertisementHtml
{
    public function sanitize(?string $html): ?string
    {
        if (! is_string($html) || trim($html) === '') {
            return null;
        }
        if (! class_exists(DOMDocument::class)) {
            return strip_tags($html, '<div><span><p><a><img><strong><em><small><br>');
        }
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="ad-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $document->getElementById('ad-root');
        if (! $root) {
            return null;
        }
        $allowed = ['div', 'span', 'p', 'a', 'img', 'strong', 'b', 'em', 'i', 'small', 'br'];
        foreach (iterator_to_array($root->getElementsByTagName('*')) as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }
            if (! in_array(strtolower($element->tagName), $allowed, true)) {
                $element->remove();

                continue;
            }
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                if (str_starts_with($name, 'on') || ! in_array($name, ['href', 'src', 'alt', 'title', 'class', 'width', 'height', 'target', 'rel'], true)) {
                    $element->removeAttribute($name);
                }
            }
            foreach (['href', 'src'] as $attribute) {
                if ($element->hasAttribute($attribute) && AdvertisementUrl::normalize($element->getAttribute($attribute)) === null) {
                    $element->removeAttribute($attribute);
                }
            }
        }
        $output = '';
        foreach ($root->childNodes as $node) {
            $output .= $document->saveHTML($node) ?: '';
        }

        return trim($output) ?: null;
    }
}
