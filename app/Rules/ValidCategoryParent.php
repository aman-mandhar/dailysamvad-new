<?php

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCategoryParent implements ValidationRule
{
    public function __construct(private readonly ?Category $category = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value) || ! $this->category?->exists) {
            return;
        }

        $categoryId = $this->category->getKey();
        $parentId = (int) $value;
        $visited = [];

        while ($parentId !== 0) {
            if ($parentId === $categoryId) {
                $fail('A category cannot be its own parent or a child of one of its descendants.');

                return;
            }

            if (isset($visited[$parentId])) {
                $fail('The selected parent category belongs to an invalid circular hierarchy.');

                return;
            }

            $visited[$parentId] = true;
            $parentId = (int) (Category::query()->whereKey($parentId)->value('parent_id') ?? 0);
        }
    }
}
