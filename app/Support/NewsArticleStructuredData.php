<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Support\Collection;

class NewsArticleStructuredData
{
    /**
     * @param  Collection<int, array{label: string, url: ?string, current: bool}>  $breadcrumbs
     * @return array{article: array<string, mixed>, breadcrumbs: array<string, mixed>}
     */
    public function build(Post $post, string $canonicalUrl, string $description, Collection $breadcrumbs): array
    {
        $category = $post->primaryCategory->first();
        $article = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $post->title,
            'description' => $description,
            'image' => $post->featured_image_url ? [$post->featured_image_url] : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
            'author' => $post->author ? ['@type' => 'Person', 'name' => $post->author->name] : null,
            'publisher' => ['@type' => 'Organization', 'name' => config('organization.organization_name')],
            'articleSection' => $category?->name,
            'keywords' => $post->tags->isNotEmpty() ? $post->tags->pluck('name')->implode(', ') : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $items = $breadcrumbs->values()->map(fn (array $item, int $index): array => array_filter([
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['label'],
            'item' => $item['url'],
        ], static fn (mixed $value): bool => $value !== null))->all();

        return [
            'article' => $article,
            'breadcrumbs' => ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items],
        ];
    }
}
