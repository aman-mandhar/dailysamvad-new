<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class UserAdministration
{
    /** @var array<string, int> */
    private const ROLE_AUTHORITY = [
        'super-admin' => 700,
        'admin' => 600,
        'editor' => 500,
        'reviewer' => 400,
        'seo-manager' => 300,
        'media-manager' => 300,
        'reporter' => 200,
    ];

    public static function canManageRoles(User $actor, ?User $target = null): bool
    {
        if (! $actor->hasPermissionTo('manage roles')) {
            return false;
        }

        if ($target === null || $actor->hasRole('super-admin')) {
            return true;
        }

        if ($actor->is($target)) {
            return false;
        }

        return self::authority($actor) > self::authority($target);
    }

    /** @return Collection<int, Role> */
    public static function assignableRoles(User $actor): Collection
    {
        return Role::query()
            ->orderBy('name')
            ->get()
            ->filter(function (Role $role) use ($actor): bool {
                if ($role->name === 'super-admin') {
                    return $actor->hasRole('super-admin');
                }

                return $actor->hasRole('super-admin')
                    || (self::ROLE_AUTHORITY[$role->name] ?? 0) < self::authority($actor);
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

        if (! isset($errors['roles']) && ! $actor->hasRole('super-admin') && self::requestedAuthority($requestedRoles) > self::authority($actor)) {
            $errors['roles'] = 'You cannot assign a role with greater authority than your own.';
        }

        if (! isset($errors['roles']) && $actor->is($target) && ! $actor->hasRole('super-admin') && self::requestedAuthority($requestedRoles) > self::authority($target)) {
            $errors['roles'] = 'You cannot promote your own account.';
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

        if ($requestedRoles->contains('super-admin') && ! $actor->hasRole('super-admin')) {
            return ['roles' => 'Only a super-admin may assign the super-admin role.'];
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

    private static function authority(User $user): int
    {
        return self::requestedAuthority($user->getRoleNames());
    }

    /** @param Collection<int, string> $roles */
    private static function requestedAuthority(Collection $roles): int
    {
        return (int) $roles->map(fn (string $role): int => self::ROLE_AUTHORITY[$role] ?? 0)->max();
    }
}
