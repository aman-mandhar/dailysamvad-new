<?php

namespace App\Queries;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ArchiveQuery
{
    /** @return array<string, mixed> */
    public function category(string $slug): array
    {
        $category = Category::query()->active()->where('slug', $slug)->firstOrFail();

        return $this->data(
            type: 'category',
            title: $category->name,
            description: $category->description,
            metaTitle: $category->meta_title ?: $category->name.' News',
            metaDescription: $category->meta_description ?: $category->description,
            canonical: route('categories.show', $category->slug),
            posts: $category->posts()->published()->latestPublished()->with('primaryCategory:id,name,slug')->paginate(12),
            entity: $category,
        );
    }

    /** @return array<string, mixed> */
    public function tag(string $slug): array
    {
        $tag = Tag::query()->where('slug', $slug)->firstOrFail();

        return $this->data(
            type: 'tag',
            title: $tag->name,
            description: $tag->description,
            metaTitle: $tag->meta_title ?: $tag->name.' News',
            metaDescription: $tag->meta_description ?: $tag->description,
            canonical: route('tags.show', $tag->slug),
            posts: $tag->posts()->published()->latestPublished()->with('primaryCategory:id,name,slug')->paginate(12),
            entity: $tag,
        );
    }

    /** @return array<string, mixed> */
    public function author(string $username): array
    {
        $author = User::query()
            ->select(['id', 'name', 'username', 'slug', 'bio', 'avatar_path', 'facebook_url', 'x_url', 'instagram_url', 'youtube_url'])
            ->where('username', $username)
            ->firstOrFail();

        return $this->data(
            type: 'author',
            title: $author->name,
            description: $author->bio,
            metaTitle: $author->name.' - Author at Daily Samvad',
            metaDescription: $author->bio ?: 'Read the latest published reports by '.$author->name.' on Daily Samvad.',
            canonical: route('authors.show', $author->username),
            posts: $this->postQuery()->where('author_id', $author->getKey())->paginate(12),
            entity: $author,
        );
    }

    /** @return Builder<Post> */
    private function postQuery(): Builder
    {
        return Post::query()
            ->published()
            ->latestPublished()
            ->with('primaryCategory:id,name,slug');
    }

    /**
     * @return array<string, mixed>
     */
    private function data(
        string $type,
        string $title,
        ?string $description,
        string $metaTitle,
        ?string $metaDescription,
        string $canonical,
        LengthAwarePaginator $posts,
        Category|Tag|User $entity,
    ): array {
        $posts->withQueryString();

        return compact('type', 'title', 'description', 'metaTitle', 'metaDescription', 'canonical', 'posts', 'entity');
    }
}
