<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view posts',
            'create posts',
            'update posts',
            'delete posts',
            'publish posts',
            'review posts',
            'manage categories',
            'manage tags',
            'manage pages',
            'manage media',
            'manage users',
            'manage roles',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $rolePermissions = [
            'super-admin' => $permissions,
            'admin' => [
                'view posts',
                'create posts',
                'update posts',
                'delete posts',
                'publish posts',
                'review posts',
                'manage categories',
                'manage tags',
                'manage pages',
                'manage media',
                'manage users',
                'manage settings',
            ],
            'editor' => [
                'view posts',
                'create posts',
                'update posts',
                'delete posts',
                'publish posts',
                'review posts',
                'manage categories',
                'manage tags',
                'manage pages',
                'manage media',
            ],
            'reporter' => [
                'view posts',
                'create posts',
                'update posts',
                'manage media',
            ],
            'reviewer' => [
                'view posts',
                'review posts',
            ],
            'seo-manager' => [
                'view posts',
                'update posts',
                'manage categories',
                'manage tags',
                'manage pages',
            ],
            'media-manager' => [
                'view posts',
                'manage media',
            ],
        ];

        foreach ($rolePermissions as $roleName => $assignedPermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($assignedPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
