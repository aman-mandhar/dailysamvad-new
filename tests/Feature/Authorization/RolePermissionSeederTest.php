<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
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
        'reviewer',
        'seo-manager',
        'media-manager',
    ];

    /**
     * @var list<string>
     */
    private array $permissions = [
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

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseCount('roles', count($this->roles));
        $this->assertDatabaseCount('permissions', count($this->permissions));
    }
}
