<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $roles = [
        'super-admin',
        'admin',
        'editor',
        'reporter',
        'author',
        'subscriber',
        'reviewer',
        'seo-manager',
        'media-manager',
        'analytics-manager',
        'contributor',
    ];

    /**
     * @var list<string>
     */
    private array $permissions = RolesAndPermissionsSeeder::PERMISSIONS;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_roles_are_created(): void
    {
        $this->assertEqualsCanonicalizing(
            $this->roles,
            Role::query()->pluck('name')->all(),
        );
    }

    public function test_permissions_are_created(): void
    {
        $this->assertEqualsCanonicalizing(
            $this->permissions,
            Permission::query()->pluck('name')->all(),
        );
    }

    public function test_super_admin_has_every_permission(): void
    {
        $superAdmin = Role::findByName('super-admin');

        $this->assertTrue($superAdmin->hasAllPermissions($this->permissions));
    }

    public function test_reporter_cannot_publish_posts(): void
    {
        $reporter = User::factory()->create();
        $reporter->assignRole('reporter');

        $this->assertFalse($reporter->can('publish posts'));
    }

    public function test_editor_can_publish_posts(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $this->assertTrue($editor->can('publish posts'));
    }

    public function test_only_super_admin_and_admin_roles_can_update_all_posts(): void
    {
        $this->assertTrue(Role::findByName('super-admin')->hasPermissionTo('update all posts'));
        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('update all posts'));
        $this->assertFalse(Role::findByName('editor')->hasPermissionTo('update all posts'));
        $this->assertTrue(Role::findByName('editor')->hasPermissionTo('update own posts'));
    }

    public function test_reseeding_removes_stale_all_post_editing_permission_from_editor(): void
    {
        $editor = Role::findByName('editor');
        $editor->givePermissionTo('update all posts');

        $this->seed(RolePermissionSeeder::class);

        $this->assertFalse($editor->fresh()->hasPermissionTo('update all posts'));
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseCount('roles', count($this->roles));
        $this->assertDatabaseCount('permissions', count($this->permissions));
    }

    public function test_custom_roles_permissions_assignments_and_direct_permissions_are_preserved(): void
    {
        $customPermission = Permission::findOrCreate('custom newsroom permission', 'web');
        $customRole = Role::findOrCreate('custom-newsroom-role', 'web');
        $customRole->givePermissionTo($customPermission);
        $user = User::factory()->create();
        $user->assignRole($customRole);
        $user->givePermissionTo($customPermission);

        $this->seed(RolePermissionSeeder::class);

        $this->assertTrue($user->fresh()->hasRole('custom-newsroom-role'));
        $this->assertTrue($user->fresh()->hasDirectPermission('custom newsroom permission'));
        $this->assertTrue($customRole->fresh()->hasPermissionTo('custom newsroom permission'));
        $this->assertSame('web', Role::findByName('analytics-manager')->guard_name);
        $this->assertSame('web', Permission::findByName('view all analytics')->guard_name);
    }
}
