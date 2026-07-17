<?php

namespace App\Queries;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class BreakingNewsQuery
{
    /** @return Collection<int, Post> */
    public function latest(int $limit = 12): Collection
    {
        if (! Schema::hasTable('posts')) {
            return new Collection;
        }

        return Post::query()
            ->select([
                'id',
                'author_id',
                'title',
                'slug',
                'featured_image',
                'featured_image_alt',
                'published_at',
                'is_breaking',
            ])
            ->published()
            ->breaking()
            ->with([
                'author:id,name',
                'primaryCategory:id,name,slug',
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(max(1, min($limit, 15)))
            ->get();
    }
}
