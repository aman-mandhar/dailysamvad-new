<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage categories');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('manage categories');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage categories');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('manage categories');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('manage categories');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage categories');
    }
}
