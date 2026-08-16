<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public const GUARD = 'web';

    /** @var list<string> */
    public const CANONICAL_ROLES = [
        'super-admin', 'admin', 'editor', 'reviewer', 'reporter', 'seo-manager',
        'media-manager', 'analytics-manager', 'contributor', 'subscriber',
    ];

    /** @var list<string> */
    public const PERMISSIONS = [
        'access admin panel', 'view admin dashboard',
        'view posts', 'view own posts', 'view assigned posts', 'view all posts', 'create posts',
        'update own posts', 'update assigned posts', 'update all posts', 'delete own posts', 'delete all posts',
        'submit posts for review', 'review posts', 'request post corrections', 'approve posts', 'reject posts',
        'assign reviewers', 'view workflow history', 'schedule posts', 'publish posts', 'archive posts', 'restore posts',
        'view categories', 'manage categories', 'view tags', 'manage tags',
        'view media', 'upload media', 'update own media', 'update all media', 'delete own media', 'delete all media', 'manage media',
        'view seo', 'manage seo',
        'view own analytics', 'view editorial analytics', 'view all analytics', 'export analytics',
        'view users', 'create users', 'update users', 'disable users', 'delete users', 'manage users',
        'view roles', 'manage roles and permissions',
        'manage advertisements', 'view advertisements', 'create advertisements', 'update advertisements',
        'delete advertisements', 'restore advertisements', 'publish advertisements', 'pause advertisements',
        'update advertisements from frontend', 'view advertisement analytics', 'manage advertisement provider code',
        'manage advertisement settings', 'manage settings', 'manage own profile', 'manage pages',
        'view push notifications', 'create push notifications', 'update push notifications',
        'delete push notifications', 'send push notifications',
        'view push analytics',

        // Compatibility aliases retained for existing roles, users, policies, and extensions.
        'edit own posts', 'edit all posts', 'delete own drafts', 'submit own posts',
        'view analytics', 'manage analytics', 'manage roles', 'manage permissions',
        'update posts', 'delete posts',
    ];

    /** @var array<string, list<string>> */
    public const ROLE_PERMISSIONS = [
        'admin' => [
            'access admin panel', 'view admin dashboard', 'view posts', 'view own posts', 'view all posts',
            'create posts', 'update own posts', 'update all posts', 'delete own posts', 'delete all posts',
            'submit posts for review', 'review posts', 'request post corrections', 'approve posts', 'reject posts',
            'assign reviewers', 'view workflow history', 'schedule posts', 'publish posts', 'archive posts', 'restore posts',
            'view categories', 'manage categories', 'view tags', 'manage tags',
            'view media', 'upload media', 'update all media', 'delete all media', 'manage media',
            'view seo', 'manage seo', 'view editorial analytics',
            'view users', 'create users', 'update users', 'disable users', 'delete users', 'manage users',
            'manage advertisements', 'view advertisements', 'create advertisements', 'update advertisements',
            'delete advertisements', 'restore advertisements', 'publish advertisements', 'pause advertisements',
            'update advertisements from frontend', 'view advertisement analytics', 'manage advertisement provider code',
            'manage advertisement settings', 'manage own profile', 'manage pages',
            'view push notifications', 'create push notifications', 'update push notifications',
            'delete push notifications', 'send push notifications',
            'view push analytics',
        ],
        'editor' => [
            'access admin panel', 'view admin dashboard', 'view posts', 'view own posts', 'view all posts',
            'create posts', 'update own posts', 'delete own posts',
            'submit posts for review', 'review posts', 'request post corrections', 'approve posts', 'reject posts',
            'assign reviewers', 'view workflow history', 'schedule posts', 'publish posts', 'archive posts', 'restore posts',
            'view categories', 'manage categories', 'view tags', 'manage tags',
            'view media', 'upload media', 'update all media', 'delete own media', 'manage media',
            'view editorial analytics', 'view advertisements', 'update advertisements', 'publish advertisements',
            'pause advertisements', 'update advertisements from frontend', 'view advertisement analytics', 'manage own profile', 'manage pages',
            'view push notifications', 'create push notifications', 'update push notifications',
            'delete push notifications',
        ],
        'reviewer' => [
            'access admin panel', 'view admin dashboard', 'view posts', 'view assigned posts',
            'review posts', 'request post corrections', 'approve posts', 'reject posts',
            'view workflow history', 'view categories', 'view tags', 'view media', 'manage own profile',
        ],
        'reporter' => [
            'access admin panel', 'view admin dashboard', 'view posts', 'view own posts', 'create posts',
            'update own posts', 'delete own posts', 'submit posts for review',
            'view workflow history',
            'view media', 'upload media', 'update own media', 'delete own media',
            'view own analytics', 'manage own profile',
        ],
        'seo-manager' => [
            'access admin panel', 'view admin dashboard', 'view posts', 'view all posts',
            'view categories', 'view tags', 'view media', 'view seo', 'manage seo',
            'view editorial analytics', 'view advertisements', 'manage own profile',
        ],
        'media-manager' => [
            'access admin panel', 'view admin dashboard', 'view media', 'upload media',
            'update all media', 'delete all media', 'manage media', 'manage own profile',
        ],
        'analytics-manager' => [
            'access admin panel', 'view admin dashboard', 'view all analytics', 'export analytics', 'view advertisements', 'view advertisement analytics',
            'view push notifications', 'view push analytics', 'manage own profile',
        ],
        'contributor' => [
            'access admin panel', 'view admin dashboard', 'view posts', 'view own posts',
            'create posts', 'update own posts', 'delete own posts', 'submit posts for review', 'manage own profile',
        ],
        'subscriber' => ['manage own profile'],
        // Active legacy compatibility role; intentionally mirrors contributor.
        'author' => [
            'access admin panel', 'view admin dashboard', 'view posts', 'view own posts',
            'create posts', 'update own posts', 'delete own posts', 'submit posts for review', 'manage own profile',
        ],
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        // Clear stale permission cache before seeding.
        $registrar->forgetCachedPermissions();

        // Create all permissions first.
        foreach (self::PERMISSIONS as $permissionName) {
            Permission::findOrCreate($permissionName, self::GUARD);
        }

        // Important: refresh cache after permissions have been created.
        $registrar->forgetCachedPermissions();

        // Create all canonical and compatibility roles.
        foreach ([...self::CANONICAL_ROLES, 'author'] as $roleName) {
            Role::findOrCreate($roleName, self::GUARD);
        }

        // Assign the configured permission set to each role.
        foreach (self::ROLE_PERMISSIONS as $roleName => $permissionNames) {
            $role = Role::findByName($roleName, self::GUARD);

            $permissions = Permission::query()
                ->where('guard_name', self::GUARD)
                ->whereIn('name', $permissionNames)
                ->get();

            $missingPermissions = array_values(array_diff(
                $permissionNames,
                $permissions->pluck('name')->all()
            ));

            if ($missingPermissions !== []) {
                throw new \RuntimeException(
                    "Missing permissions for role [{$roleName}]: "
                    .implode(', ', $missingPermissions)
                );
            }

            $role->givePermissionTo($permissions);

            if ($roleName === 'editor') {
                $role->revokePermissionTo(['update all posts', 'edit all posts', 'update posts']);
            }
        }

        // Super Admin receives every permission.
        $superAdmin = Role::findByName('super-admin', self::GUARD);

        $superAdmin->givePermissionTo(
            Permission::query()
                ->where('guard_name', self::GUARD)
                ->get()
        );

        // Clear cache after all assignments.
        $registrar->forgetCachedPermissions();
    }
}
