<?php

namespace App\Policies;

use App\Models\User;
use App\Support\UserAdministration;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage users');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage users');
    }

    public function delete(User $user, User $model): Response
    {
        if (! $user->hasPermissionTo('manage users')) {
            return Response::deny('You are not authorized to delete users.');
        }

        if ($user->is($model)) {
            return Response::deny('You cannot delete your own account.');
        }

        if (UserAdministration::isFinalActiveSuperAdmin($model)) {
            return Response::deny('The final active super-admin cannot be deleted.');
        }

        if ($model->posts()->exists()) {
            return Response::deny('Reassign all attributed posts before deleting this author.');
        }

        return Response::allow();
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage users');
    }

    public function manageRoles(User $user, ?User $model = null): bool
    {
        return UserAdministration::canManageRoles($user, $model);
    }
}
