<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage tags');
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('manage tags');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage tags');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('manage tags');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('manage tags');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage tags');
    }
}
