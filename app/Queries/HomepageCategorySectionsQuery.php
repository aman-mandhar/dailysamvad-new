<?php

namespace App\Queries;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class HomepageCategorySectionsQuery
{
    /** @return Collection<int, array<string, mixed>> */
    public function get(): Collection
    {
        $definitions = collect(config('homepage.sections', []));

        if ($definitions->isEmpty() || ! Schema::hasTable('categories') || ! Schema::hasTable('posts')) {
            return collect();
        }

        $slugs = $definitions->flatMap(fn (array $section): array => $section['slugs'] ?? [])->unique()->values();
        $names = $definitions->flatMap(fn (array $section): array => $section['names'] ?? [])->unique()->values();
        $categories = Category::query()
            ->select(['id', 'name', 'slug', 'is_active'])
            ->active()
            ->where(fn (Builder $query): Builder => $query->whereIn('slug', $slugs)->orWhereIn('name', $names))
            ->get();

        $resolved = $definitions->map(fn (array $definition): array => [
            'definition' => $definition,
            'category' => $this->resolveCategory($categories, $definition),
        ]);

        $categoryIds = $resolved->pluck('category')->filter()->pluck('id');
        $maximumLimit = max(1, (int) $definitions->max('limit'));

        $loadedCategories = Category::query()
            ->select(['id', 'name', 'slug'])
            ->whereIn('id', $categoryIds)
            ->with(['posts' => fn ($query) => $query
                ->select($this->postColumns())
                ->published()
                ->with(['author:id,name', 'primaryCategory:id,name,slug'])
                ->orderByDesc('published_at')
                ->orderByDesc('posts.id')
                ->limit($maximumLimit),
            ])
            ->get()
            ->keyBy('id');

        return $resolved->map(function (array $item) use ($loadedCategories): array {
            $definition = $item['definition'];
            $category = $item['category'] ? $loadedCategories->get($item['category']->id) : null;
            $posts = $category?->posts?->take((int) $definition['limit'])->values() ?? collect();
            $source = $category ? 'category' : null;

            if ($posts->isEmpty() && ($definition['fallback'] ?? null) === 'breaking-flag' && ! $category) {
                $posts = $this->breakingFallback((int) $definition['limit']);
                $source = 'breaking-flag';
            }

            return [
                ...$definition,
                'category' => $category,
                'posts' => $posts,
                'url' => $category ? route('categories.show', $category->slug) : null,
                'source' => $source,
            ];
        })->filter(fn (array $section): bool => $section['posts']->isNotEmpty())->values();
    }

    /** @param Collection<int, Category> $categories */
    private function resolveCategory(Collection $categories, array $definition): ?Category
    {
        foreach ($definition['slugs'] ?? [] as $slug) {
            if ($category = $categories->firstWhere('slug', $slug)) {
                return $category;
            }
        }

        foreach ($definition['names'] ?? [] as $name) {
            if ($category = $categories->firstWhere('name', $name)) {
                return $category;
            }
        }

        return null;
    }

    /** @return Collection<int, Post> */
    private function breakingFallback(int $limit): Collection
    {
        return Post::query()->select($this->postColumns())->published()->breaking()
            ->with(['author:id,name', 'primaryCategory:id,name,slug'])
            ->orderByDesc('published_at')->orderByDesc('id')->limit($limit)->get();
    }

    /** @return list<string> */
    private function postColumns(): array
    {
        return ['posts.id', 'posts.author_id', 'posts.title', 'posts.slug', 'posts.excerpt', 'posts.meta_title', 'posts.featured_image', 'posts.featured_image_alt', 'posts.published_at'];
    }
}
