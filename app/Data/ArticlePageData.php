<?php

namespace App\Data;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final readonly class ArticlePageData
{
    /**
     * @param  Collection<int, ArticleContentBlockData>  $contentBlocks
     * @param  Collection<int, array{label: string, url: ?string, current: bool}>  $breadcrumbs
     * @param  array<string, string>  $shareUrls
     * @param  Collection<int, SidebarWidgetData>  $sidebarWidgets
     * @param  EloquentCollection<int, Post>  $relatedPosts
     */
    public function __construct(
        public Post $post,
        public Collection $contentBlocks,
        public Collection $breadcrumbs,
        public array $shareUrls,
        public int $readingTime,
        public ?Post $previousPost,
        public ?Post $nextPost,
        public EloquentCollection $relatedPosts,
        public Collection $sidebarWidgets,
        public bool $sidebarSticky,
        public AdvertisementData $topAdvertisement,
        public AdvertisementData $bottomAdvertisement,
        public string $canonicalUrl,
        public string $seoTitle,
        public string $seoDescription,
        public string $robots,
    ) {}
}
