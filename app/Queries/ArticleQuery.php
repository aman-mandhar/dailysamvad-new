<?php

namespace App\Queries;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ArticleQuery
{
    /** @return array<string, mixed> */
    public function find(string $slug): array
    {
        $post = Post::query()
            ->published()
            ->with([
                'author:id,name,slug,bio,avatar_path',
                'primaryCategory:id,name,slug',
                'categories:id,name,slug',
                'tags:id,name,slug',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return [
            'post' => $post,
            'relatedPosts' => $this->relatedPosts($post),
            'previousPost' => $this->previousPost($post),
            'nextPost' => $this->nextPost($post),
        ];
    }

    /** @return Collection<int, Post> */
    private function relatedPosts(Post $post): Collection
    {
        $primaryCategoryId = $post->primaryCategory->first()?->getKey();

        if ($primaryCategoryId === null) {
            return new Collection;
        }

        return $this->cardQuery()
            ->whereKeyNot($post->getKey())
            ->whereHas('categories', fn (Builder $query): Builder => $query->whereKey($primaryCategoryId))
            ->latestPublished()
            ->limit(6)
            ->get();
    }

    private function previousPost(Post $post): ?Post
    {
        return $this->navigationQuery()
            ->where(function (Builder $query) use ($post): void {
                $query->where('published_at', '<', $post->published_at)
                    ->orWhere(function (Builder $query) use ($post): void {
                        $query->where('published_at', $post->published_at)
                            ->whereKey('<', $post->getKey());
                    });
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    private function nextPost(Post $post): ?Post
    {
        return $this->navigationQuery()
            ->where(function (Builder $query) use ($post): void {
                $query->where('published_at', '>', $post->published_at)
                    ->orWhere(function (Builder $query) use ($post): void {
                        $query->where('published_at', $post->published_at)
                            ->whereKey('>', $post->getKey());
                    });
            })
            ->orderBy('published_at')
            ->orderBy('id')
            ->first();
    }

    /** @return Builder<Post> */
    private function cardQuery(): Builder
    {
        return Post::query()
            ->select([
                'id',
                'title',
                'slug',
                'excerpt',
                'meta_title',
                'featured_image',
                'featured_image_alt',
                'published_at',
            ])
            ->with('primaryCategory:id,name,slug');
    }

    /** @return Builder<Post> */
    private function navigationQuery(): Builder
    {
        return Post::query()
            ->select(['id', 'title', 'slug', 'published_at'])
            ->published();
    }
}
