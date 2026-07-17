<?php

namespace App\Queries;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class HomepageQuery
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        if (! Schema::hasTable('posts') || ! Schema::hasTable('categories')) {
            return $this->emptyData();
        }

        $heroPost = $this->publishedPosts()
            ->featured()
            ->latestPublished()
            ->first();

        $heroPost ??= $this->publishedPosts()
            ->latestPublished()
            ->first();

        return [
            'heroPost' => $heroPost,
            'breakingNews' => $this->publishedPosts()
                ->breaking()
                ->latestPublished()
                ->limit(10)
                ->get(),
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
        ];
    }

    /** @return array<string, mixed> */
    private function emptyData(): array
    {
        return [
            'heroPost' => null,
            'breakingNews' => new Collection,
            'latestPosts' => new LengthAwarePaginator(
                items: [],
                total: 0,
                perPage: 12,
                currentPage: LengthAwarePaginator::resolveCurrentPage(),
                options: ['path' => LengthAwarePaginator::resolveCurrentPath()],
            ),
            'featuredPosts' => new Collection,
            'categoryBlocks' => new Collection,
        ];
    }

    /** @return Builder<Post> */
    private function publishedPosts(): Builder
    {
        return Post::query()
            ->select([
                'id',
                'title',
                'slug',
                'excerpt',
                'content',
                'meta_title',
                'featured_image',
                'featured_image_alt',
                'published_at',
                'is_breaking',
                'is_featured',
            ])
            ->with('primaryCategory:id,name,slug');
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
                    'posts.featured_image_alt',
                    'posts.published_at',
                ])
                ->published()
                ->orderByDesc('published_at')
                ->limit(5),
            ])
            ->ordered()
            ->limit(6)
            ->get();
    }
}
