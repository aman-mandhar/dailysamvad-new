<?php

namespace App\Queries;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SearchQuery
{
    public function search(string $term): LengthAwarePaginator
    {
        return Post::query()
            ->select(['id', 'title', 'slug', 'excerpt', 'content', 'meta_title', 'featured_image', 'featured_image_alt', 'published_at'])
            ->published()
            ->when($term !== '', fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query
                    ->where('title', 'like', "%{$term}%")
                    ->orWhere('content', 'like', "%{$term}%"),
            ), fn (Builder $query): Builder => $query->whereRaw('1 = 0'))
            ->with('primaryCategory:id,name,slug')
            ->orderByDesc('published_at')
            ->paginate((int) config('publication.search_per_page', 12))
            ->withQueryString();
    }
}
