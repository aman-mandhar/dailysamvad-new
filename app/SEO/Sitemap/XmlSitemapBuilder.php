<?php

namespace App\SEO\Sitemap;

use XMLWriter;

class XmlSitemapBuilder
{
    public function document(string $root, array $namespaces, callable $write): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement($root);
        foreach ($namespaces as $prefix => $namespace) {
            $prefix === '' ? $xml->writeAttribute('xmlns', $namespace) : $xml->writeAttribute('xmlns:'.$prefix, $namespace);
        }
        $write($xml);
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    public function url(XMLWriter $xml, string $location, ?string $lastModified = null): void
    {
        $xml->startElement('url');
        $xml->writeElement('loc', $this->clean($location));
        if ($lastModified) {
            $xml->writeElement('lastmod', $lastModified);
        }
        $xml->endElement();
    }

    public function sitemap(XMLWriter $xml, string $location, ?string $lastModified = null): void
    {
        $xml->startElement('sitemap');
        $xml->writeElement('loc', $this->clean($location));
        if ($lastModified) {
            $xml->writeElement('lastmod', $lastModified);
        }
        $xml->endElement();
    }

    public function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/isu', ' ', $value) ?? '';
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $value = preg_replace('/\[[^\]]+\]/u', ' ', $value) ?? '';
        $value = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

        return $value !== '' ? mb_convert_encoding($value, 'UTF-8', 'UTF-8') : null;
    }

    private function clean(string $value): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    }
}
