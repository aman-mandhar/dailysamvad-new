<?php

namespace App\Policies;

use App\Models\Advertisement;
use App\Models\User;

class AdvertisementPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_active ? null : false;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view advertisements');
    }

    public function view(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermissionTo('view advertisements');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create advertisements');
    }

    public function update(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermissionTo('update advertisements');
    }

    public function delete(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermissionTo('delete advertisements');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete advertisements') && ! $user->hasRole('editor');
    }

    public function restore(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermissionTo('restore advertisements');
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasPermissionTo('restore advertisements');
    }

    public function forceDelete(User $user, Advertisement $advertisement): bool
    {
        return $user->hasRole('super-admin');
    }

    public function updateFromFrontend(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermissionTo('update advertisements from frontend') && $this->update($user, $advertisement);
    }

    public function publish(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermissionTo('publish advertisements');
    }

    public function pause(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermissionTo('pause advertisements');
    }

    public function viewAnalytics(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermissionTo('view advertisement analytics');
    }

    public function manageProviderCode(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermissionTo('manage advertisement provider code');
    }
}
