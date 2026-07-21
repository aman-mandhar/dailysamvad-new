<?php

namespace Tests\Feature\Authorization;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use App\Policies\UserPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolePermissionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_roles_permissions_and_mappings_are_idempotent(): void
    {
        Permission::findOrCreate('custom production permission');
        $admin = Role::findOrCreate('admin');
        $admin->givePermissionTo('custom production permission');

        $this->seed(RolesAndPermissionsSeeder::class);
        $roleCount = Role::count();
        $permissionCount = Permission::count();
        $this->seed(RolesAndPermissionsSeeder::class);

        foreach (['super-admin', 'admin', 'editor', 'reporter', 'author', 'subscriber'] as $role) {
            $this->assertTrue(Role::findByName($role)->exists);
        }
        foreach (RolesAndPermissionsSeeder::PERMISSIONS as $permission) {
            $this->assertTrue(Permission::findByName($permission)->exists);
        }
        $this->assertSame($roleCount, Role::count());
        $this->assertSame($permissionCount, Permission::count());
        $this->assertTrue($admin->fresh()->hasPermissionTo('custom production permission'));
        $this->assertTrue(Role::findByName('super-admin')->hasPermissionTo('manage permissions'));
        $this->assertFalse(Role::findByName('subscriber')->hasPermissionTo('access admin panel'));
    }

    public function test_filament_access_is_permission_and_status_driven(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $panel = Filament::getPanel('admin');
        $subscriber = User::factory()->create();
        $subscriber->assignRole('subscriber');
        $reporter = User::factory()->create();
        $reporter->assignRole('reporter');

        $this->assertFalse($subscriber->canAccessPanel($panel));
        $this->assertTrue($reporter->canAccessPanel($panel));

        Role::findByName('reporter')->revokePermissionTo('access admin panel');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertFalse($reporter->fresh()->canAccessPanel($panel));

        $reporter->update(['is_active' => false]);
        $this->assertFalse($reporter->fresh()->canAccessPanel($panel));
    }

    public function test_post_policy_distinguishes_ownership_and_publish_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $reporter = User::factory()->create();
        $reporter->assignRole('reporter');
        $other = User::factory()->create();
        $ownDraft = Post::factory()->create(['author_id' => $reporter, 'status' => PostStatus::Draft]);
        $otherDraft = Post::factory()->create(['author_id' => $other, 'status' => PostStatus::Draft]);

        $this->assertTrue($reporter->can('update', $ownDraft));
        $this->assertFalse($reporter->can('update', $otherDraft));
        $this->assertFalse($reporter->can('publish', $ownDraft));

        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $pending = Post::factory()->create(['status' => PostStatus::PendingReview]);
        $this->assertTrue($editor->can('update', $pending));
        $this->assertTrue($editor->can('publish', $pending));
    }

    public function test_final_super_admin_is_protected_and_no_account_is_assigned_implicitly(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue((new UserPolicy)->delete($admin, $superAdmin)->denied());
        $this->assertSame(1, User::role('super-admin')->count());
    }

    public function test_role_assignments_resolve_and_survive_permission_cache_reset(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('reporter');

        $assignment = DB::table('model_has_roles')->sole();
        $this->assertSame($user->id, (int) $assignment->model_id);
        $this->assertSame(User::class, $assignment->model_type);
        $this->assertTrue($user->fresh()->roles->contains('name', 'reporter'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($user->fresh()->hasRole('reporter'));
        $this->assertTrue($user->fresh()->can('access admin panel'));
    }
}
