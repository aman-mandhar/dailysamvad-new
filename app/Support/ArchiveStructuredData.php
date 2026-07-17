<?php

namespace App\Support;

use Illuminate\Support\Collection;

class ArchiveStructuredData
{
    /**
     * @param  Collection<int, array{label: string, url: ?string, current: bool}>  $breadcrumbs
     * @return array{page: array<string, mixed>, breadcrumbs: array<string, mixed>}
     */
    public function build(string $type, string $title, string $description, string $url, Collection $breadcrumbs): array
    {
        return [
            'page' => [
                '@context' => 'https://schema.org',
                '@type' => $type === 'search' ? 'SearchResultsPage' : 'CollectionPage',
                'name' => $title,
                'description' => $description,
                'url' => $url,
                'isPartOf' => ['@type' => 'WebSite', 'name' => config('organization.website_name'), 'url' => route('home')],
            ],
            'breadcrumbs' => [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $breadcrumbs->values()->map(fn (array $item, int $index): array => array_filter([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                    'item' => $item['url'],
                ], fn (mixed $value): bool => $value !== null))->all(),
            ],
        ];
    }
}
