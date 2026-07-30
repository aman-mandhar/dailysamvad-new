<?php

namespace App\Queries;

use App\Models\Category;
use App\Models\Post;
use App\Services\CacheQueryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class HomepageQuery
{
    public function __construct(
        private HomepageCategorySectionsQuery $categorySections,
        private SidebarQuery $sidebar,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        if (config('cache_architecture.enabled') && config('cache_architecture.query')) {
            return app(CacheQueryService::class)->remember('query', 'homepage', 'public', 'default', (int) config('cache_architecture.ttls.short', 300), fn (): array => $this->uncachedGet());
        }

        return $this->uncachedGet();
    }

    /** @return array<string, mixed> */
    private function uncachedGet(): array
    {
        if (! Schema::hasTable('posts') || ! Schema::hasTable('categories')) {
            return $this->emptyData();
        }

        $heroPosts = $this->publishedPosts()
            ->featured()
            ->latestPublished()
            ->limit(5)
            ->get();

        if ($heroPosts->count() < 3) {
            $fallbackPosts = $this->publishedPosts()
                ->whereNotIn('id', $heroPosts->modelKeys())
                ->latestPublished()
                ->limit(3 - $heroPosts->count())
                ->get();

            $heroPosts = $heroPosts->concat($fallbackPosts)->values();
        }

        $sidebar = $this->sidebar->forHomepage();

        return [
            'heroPost' => $heroPosts->first(),
            'heroPosts' => $heroPosts,
            'latestPosts' => $this->publishedPosts()
                ->latestPublished()
                ->paginate(12)
                ->withQueryString(),
            'featuredPosts' => $this->publishedPosts()
                ->featured()
                ->latestPublished()
                ->limit(6)
                ->get(),
            'categoryBlocks' => $this->categoryBlocks(),
            'categorySections' => $this->categorySections->get(),
            'sidebarWidgets' => $sidebar['widgets'],
            'sidebarSticky' => $sidebar['sticky'],
            'homepageTopAdvertisement' => $this->sidebar->advertisement('HEADER_TOP', ['page_type' => 'home']),
            'homepageInlineAdvertisement' => $this->sidebar->advertisement('HOME_BETWEEN_SECTIONS', ['page_type' => 'home']),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyData(): array
    {
        return [
            'heroPost' => null,
            'heroPosts' => new Collection,
            'latestPosts' => new LengthAwarePaginator(
                items: [],
                total: 0,
                perPage: 12,
                currentPage: LengthAwarePaginator::resolveCurrentPage(),
                options: ['path' => LengthAwarePaginator::resolveCurrentPath()],
            ),
            'featuredPosts' => new Collection,
            'categoryBlocks' => new Collection,
            'categorySections' => new Collection,
            'sidebarWidgets' => new Collection,
            'sidebarSticky' => false,
            'homepageTopAdvertisement' => null,
            'homepageInlineAdvertisement' => null,
        ];
    }

    /** @return Builder<Post> */
    private function publishedPosts(): Builder
    {
        return Post::query()
            ->select([
                'id',
                'author_id',
                'title',
                'slug',
                'excerpt',
                'content',
                'meta_title',
                'featured_image',
                'featured_media_id',
                'featured_image_alt',
                'published_at',
                'is_breaking',
                'is_featured',
            ])
            ->published()
            ->with([
                'author:id,name',
                'primaryCategory:id,name,slug',
                'featuredMedia:id,disk,path,width,height,missing_at,metadata',
            ]);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Category> */
    private function categoryBlocks(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::query()
            ->select(['id', 'name', 'slug', 'sort_order'])
            ->active()
            ->menuVisible()
            ->whereHas('posts', fn (Builder $query): Builder => $query->published())
            ->with(['posts' => fn (BelongsToMany $query): BelongsToMany => $query
                ->select([
                    'posts.id',
                    'posts.title',
                    'posts.slug',
                    'posts.excerpt',
                    'posts.content',
                    'posts.meta_title',
                    'posts.featured_image',
                    'posts.featured_media_id',
                    'posts.featured_image_alt',
                    'posts.published_at',
                ])
                ->published()
                ->orderByDesc('published_at')
                ->limit(5),
                'posts.featuredMedia:id,disk,path,width,height,missing_at,metadata',
            ])
            ->ordered()
            ->limit(6)
            ->get();
    }
}
