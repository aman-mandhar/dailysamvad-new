<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Str;

class PostTaxonomy
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public static function validate(array $data): array
    {
        $errors = [];
        $categoryIds = self::normalizedIds($data['categories'] ?? []);
        $tagIds = self::normalizedIds($data['tags'] ?? []);
        $tagNames = self::normalizedTagNames($data['tag_names'] ?? []);
        $primaryCategoryId = filled($data['primary_category_id'] ?? null)
            ? (int) $data['primary_category_id']
            : null;

        if ($categoryIds === []) {
            $errors['categories'] = 'Select at least one category.';
        } elseif (count($categoryIds) !== count(array_unique($categoryIds))) {
            $errors['categories'] = 'Duplicate category assignments are not allowed.';
        } elseif (Category::query()->active()->whereKey($categoryIds)->count() !== count($categoryIds)) {
            $errors['categories'] = 'Every selected category must exist and be active.';
        }

        if ($primaryCategoryId === null) {
            $errors['primary_category_id'] = 'Select a primary category.';
        } elseif (! in_array($primaryCategoryId, $categoryIds, true)) {
            $errors['primary_category_id'] = 'The primary category must be one of the selected categories.';
        }

        if (count($tagIds) !== count(array_unique($tagIds))) {
            $errors['tags'] = 'Duplicate tag assignments are not allowed.';
        } elseif (Tag::query()->whereKey($tagIds)->count() !== count($tagIds)) {
            $errors['tags'] = 'Every selected tag must exist.';
        }

        if (count($tagNames) > 25) {
            $errors['tag_names'] = 'A post may have at most 25 tags.';
        } elseif (collect($tagNames)->contains(fn (string $name): bool => mb_strlen($name) > 100)) {
            $errors['tag_names'] = 'Each tag may contain at most 100 characters.';
        }

        return $errors;
    }

    public static function syncPrimaryCategory(Post $post, int $primaryCategoryId): void
    {
        $post->categories()
            ->newPivotStatement()
            ->where('post_id', $post->getKey())
            ->update(['is_primary' => false]);
        $post->categories()->updateExistingPivot($primaryCategoryId, ['is_primary' => true]);
    }

    /** @param array<int, string> $names */
    public static function syncTagsByName(Post $post, array $names): void
    {
        $tagIds = collect(self::normalizedTagNames($names))
            ->map(function (string $name): int {
                $existing = Tag::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
                if ($existing) {
                    return $existing->getKey();
                }

                $baseSlug = Str::slug($name);
                if ($baseSlug === '') {
                    $baseSlug = Str::lower(preg_replace('/\s+/u', '-', $name) ?? $name);
                }

                $slug = $baseSlug;
                $suffix = 2;
                while (Tag::query()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$suffix++;
                }

                return Tag::query()->create(['name' => $name, 'slug' => $slug])->getKey();
            })
            ->all();

        $post->tags()->sync($tagIds);
    }

    /** @return array<int, int> */
    private static function normalizedIds(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_map(static fn ($value): int => (int) $value, $values);
    }

    /** @return array<int, string> */
    private static function normalizedTagNames(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->map(fn (mixed $value): string => Str::squish(strip_tags((string) $value)))
            ->map(fn (string $value): string => ltrim($value, '#'))
            ->filter()
            ->unique(fn (string $value): string => mb_strtolower($value))
            ->values()
            ->all();
    }
}
