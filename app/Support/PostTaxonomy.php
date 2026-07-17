<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;

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

    /** @return array<int, int> */
    private static function normalizedIds(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_map(static fn ($value): int => (int) $value, $values);
    }
}
