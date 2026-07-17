<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use XMLWriter;

class SitemapXml
{
    public function write(): void
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $this->url($xml, route('home'));

        foreach (config('static-pages') as $page) {
            $this->url($xml, route($page['route']));
        }

        Category::query()->active()->select(['id', 'slug', 'updated_at'])->orderBy('id')->eachById(
            fn (Category $category) => $this->url($xml, route('categories.show', $category->slug), $category->updated_at?->toAtomString()),
        );
        Tag::query()->select(['id', 'slug', 'updated_at'])->orderBy('id')->eachById(
            fn (Tag $tag) => $this->url($xml, route('tags.show', $tag->slug), $tag->updated_at?->toAtomString()),
        );
        User::query()->select(['id', 'username', 'updated_at'])->whereNotNull('username')
            ->whereIn('id', Post::query()->published()->select('author_id')->whereNotNull('author_id'))->orderBy('id')->eachById(
                fn (User $author) => $this->url($xml, route('authors.show', $author->username), $author->updated_at?->toAtomString()),
            );
        Post::query()->published()->select(['id', 'slug', 'updated_at'])->orderBy('id')->eachById(
            fn (Post $post) => $this->url($xml, route('news.show', $post->slug), $post->updated_at?->toAtomString()),
        );

        $xml->endElement();
        $xml->endDocument();
        echo $xml->outputMemory();
    }

    private function url(XMLWriter $xml, string $location, ?string $lastModified = null): void
    {
        $xml->startElement('url');
        $xml->writeElement('loc', $location);
        if ($lastModified !== null) {
            $xml->writeElement('lastmod', $lastModified);
        }
        $xml->endElement();
    }
}
