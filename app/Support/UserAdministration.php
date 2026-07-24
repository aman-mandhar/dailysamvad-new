<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class UserAdministration
{
    public static function canManageRoles(User $actor, ?User $target = null): bool
    {
        if (! $actor->hasAnyPermission(['manage roles and permissions', 'manage roles'])) {
            return false;
        }

        if ($target === null || $actor->hasRole('super-admin')) {
            return true;
        }

        if ($actor->is($target)) {
            return false;
        }

        return ! $target->hasRole('super-admin');
    }

    /** @return Collection<int, Role> */
    public static function assignableRoles(User $actor): Collection
    {
        return Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->filter(function (Role $role) use ($actor): bool {
                if ($role->name === 'super-admin') {
                    return $actor->hasRole('super-admin');
                }

                return $role->permissions->every(fn ($permission): bool => $actor->can($permission->name));
            })
            ->values();
    }

    /**
     * @param  array<int, int|string>  $roleIds
     * @return array<string, string>
     */
    public static function validateChanges(User $actor, User $target, bool $isActive, array $roleIds): array
    {
        $errors = [];
        $requestedRoles = Role::query()->whereKey($roleIds)->pluck('name');
        $rolesChanged = $target->roles()->pluck('roles.id')->map(fn ($id): int => (int) $id)->sort()->values()->all()
            !== collect($roleIds)->map(fn ($id): int => (int) $id)->sort()->values()->all();

        if (! $isActive && $actor->is($target)) {
            $errors['is_active'] = 'You cannot deactivate your own account while using the admin panel.';
        }

        if (! $isActive && self::isFinalActiveSuperAdmin($target)) {
            $errors['is_active'] = 'The final active super-admin cannot be deactivated.';
        }

        if (! $rolesChanged) {
            return $errors;
        }

        if (! self::canManageRoles($actor, $target)) {
            $errors['roles'] = 'You are not authorized to change roles for this user.';

            return $errors;
        }

        if ($requestedRoles->contains('super-admin') && ! $actor->hasRole('super-admin')) {
            $errors['roles'] = 'Only a super-admin may assign the super-admin role.';
        }

        if (! isset($errors['roles']) && collect($roleIds)->map(fn ($id): int => (int) $id)
            ->diff(self::assignableRoles($actor)->modelKeys())->isNotEmpty()) {
            $errors['roles'] = 'You cannot assign permissions that exceed your own authority.';
        }

        if (! $requestedRoles->contains('super-admin') && self::isFinalActiveSuperAdmin($target)) {
            $errors['roles'] = 'The super-admin role cannot be removed from the final active super-admin.';
        }

        return $errors;
    }

    /**
     * @param  array<int, int|string>  $roleIds
     * @return array<string, string>
     */
    public static function validateNewUserRoles(User $actor, array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        if (! self::canManageRoles($actor)) {
            return ['roles' => 'You are not authorized to assign roles.'];
        }

        $requestedRoles = Role::query()->whereKey($roleIds)->pluck('name');

        if ($requestedRoles->count() !== collect($roleIds)->unique()->count()) {
            return ['roles' => 'One or more selected roles are invalid.'];
        }

        if ($requestedRoles->contains('super-admin') && ! $actor->hasRole('super-admin')) {
            return ['roles' => 'Only a super-admin may assign the super-admin role.'];
        }

        $assignableRoleIds = self::assignableRoles($actor)->modelKeys();

        if (collect($roleIds)->map(fn ($id): int => (int) $id)->diff($assignableRoleIds)->isNotEmpty()) {
            return ['roles' => 'You cannot assign a role at or above your own authority.'];
        }

        return [];
    }

    public static function isFinalActiveSuperAdmin(User $user): bool
    {
        return $user->is_active
            && $user->hasRole('super-admin')
            && User::query()
                ->where('is_active', true)
                ->role('super-admin')
                ->count() <= 1;
    }

}
