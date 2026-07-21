<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /** @var array<int, string> */
    public const PERMISSIONS = [
        'access admin panel',
        'view admin dashboard',
        'view posts',
        'view own posts',
        'view all posts',
        'create posts',
        'edit own posts',
        'edit all posts',
        'delete own drafts',
        'delete all posts',
        'submit own posts',
        'review posts',
        'approve posts',
        'reject posts',
        'request post corrections',
        'schedule posts',
        'publish posts',
        'manage users',
        'manage roles',
        'manage permissions',
        'manage categories',
        'manage tags',
        'manage media',
        'manage advertisements',
        'manage seo',
        'manage settings',
        'view analytics',
        'manage analytics',
        'manage own profile',

        // Retained because existing policies and workflow code use these names.
        'update posts',
        'delete posts',
        'manage pages',
    ];

    /** @var array<string, array<int, string>> */
    public const ROLE_PERMISSIONS = [
        'admin' => [
            'access admin panel', 'view admin dashboard', 'view posts', 'view own posts', 'view all posts',
            'create posts', 'edit own posts', 'edit all posts', 'delete own drafts', 'delete all posts',
            'submit own posts', 'review posts', 'approve posts', 'reject posts', 'request post corrections',
            'schedule posts', 'publish posts', 'manage users', 'manage categories', 'manage tags', 'manage media',
            'manage advertisements', 'manage seo', 'view analytics', 'manage own profile',
            'update posts', 'delete posts', 'manage pages',
        ],
        'editor' => [
            'access admin panel', 'view admin dashboard', 'view posts', 'view own posts', 'view all posts',
            'create posts', 'edit own posts', 'edit all posts', 'delete own drafts', 'submit own posts',
            'review posts', 'approve posts', 'reject posts', 'request post corrections', 'schedule posts',
            'publish posts', 'manage categories', 'manage tags', 'manage media', 'view analytics',
            'manage own profile', 'update posts', 'manage pages',
        ],
        'reporter' => [
            'access admin panel', 'view admin dashboard', 'view posts', 'view own posts', 'create posts',
            'edit own posts', 'delete own drafts', 'submit own posts', 'manage media', 'manage own profile',
        ],
        'author' => [
            'access admin panel', 'view admin dashboard', 'view posts', 'view own posts', 'create posts',
            'edit own posts', 'delete own drafts', 'submit own posts', 'manage own profile',
        ],
        'subscriber' => ['manage own profile'],
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->givePermissionTo(Permission::query()->where('guard_name', 'web')->get());

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo($permissions);
        }

        // Historical roles remain available with their former minimum access.
        foreach ([
            'reviewer' => ['view posts', 'view all posts', 'review posts'],
            'seo-manager' => ['view posts', 'view all posts', 'edit all posts', 'update posts', 'manage categories', 'manage tags', 'manage pages'],
            'media-manager' => ['view posts', 'view all posts', 'manage media'],
        ] as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo($permissions);
        }

        $registrar->forgetCachedPermissions();
    }
}
