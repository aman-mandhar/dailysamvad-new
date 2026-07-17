<?php

namespace App\Queries;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class ErrorPageQuery
{
    /** @return Collection<int, Post> */
    public function latest(): Collection
    {
        if (! Schema::hasTable('posts')) {
            return new Collection;
        }

        return Post::query()
            ->select(['id', 'title', 'slug', 'excerpt', 'meta_title', 'featured_image', 'featured_image_alt', 'published_at'])
            ->published()
            ->with('primaryCategory:id,name,slug')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();
    }
}
