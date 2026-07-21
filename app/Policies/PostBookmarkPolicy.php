<?php

namespace App\Policies;

use App\Models\PostBookmark;
use App\Models\User;

class PostBookmarkPolicy
{
    public function view(User $user, PostBookmark $bookmark): bool
    {
        return $bookmark->user_id === $user->getKey();
    }

    public function delete(User $user, PostBookmark $bookmark): bool
    {
        return $bookmark->user_id === $user->getKey();
    }
}
