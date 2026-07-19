<?php

namespace App\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class ArchivePageData
{
    /**
     * @param  Collection<int, array{label: string, url: ?string, current: bool}>  $breadcrumbs
     * @param  Collection<int, SidebarWidgetData>  $sidebarWidgets
     */
    public function __construct(
        public string $contextType,
        public string $label,
        public string $title,
        public ?string $description,
        public mixed $entity,
        public LengthAwarePaginator $posts,
        public Collection $breadcrumbs,
        public Collection $sidebarWidgets,
        public bool $sidebarSticky,
        public string $sidebarContext,
        public AdvertisementData $topAdvertisement,
        public AdvertisementData $inlineAdvertisement,
        public string $seoTitle,
        public string $seoDescription,
        public string $canonicalUrl,
        public string $robots,
        public ?string $searchQuery,
        public string $emptyState,
        public ?string $authorAvatarUrl = null,
        public array $authorSocialLinks = [],
    ) {}
}
