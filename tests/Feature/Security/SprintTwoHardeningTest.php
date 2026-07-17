<?php

namespace Tests\Feature\Security;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Support\UserAdministration;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SprintTwoHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_without_manage_roles_cannot_modify_roles(): void
    {
        $admin = $this->userWithRole('admin');
        $target = $this->userWithRole('reporter');
        $editorRole = Role::findByName('editor');

        $this->assertFalse(Gate::forUser($admin)->allows('manageRoles', $target));

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $target->getRouteKey()])
            ->set('data.roles', [$editorRole->id])
            ->call('save')
            ->assertHasFormErrors(['roles']);

        $this->assertTrue($target->refresh()->hasExactRoles(['reporter']));
    }

    public function test_non_super_admin_cannot_assign_super_admin(): void
    {
        $admin = $this->userWithRole('admin');
        $admin->givePermissionTo('manage roles');
        $target = $this->userWithRole('reporter');

        $errors = UserAdministration::validateChanges(
            $admin,
            $target,
            true,
            [Role::findByName('super-admin')->id],
        );

        $this->assertSame('Only a super-admin may assign the super-admin role.', $errors['roles']);
        $this->assertFalse($target->refresh()->hasRole('super-admin'));
    }

    public function test_super_admin_can_assign_permitted_roles(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $target = User::factory()->create();
        $roles = Role::query()->whereIn('name', ['editor', 'reporter'])->pluck('id')->all();

        Livewire::actingAs($superAdmin)
            ->test(EditUser::class, ['record' => $target->getRouteKey()])
            ->set('data.roles', $roles)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($target->refresh()->hasAllRoles(['editor', 'reporter']));
    }

    public function test_current_user_cannot_delete_themselves(): void
    {
        $admin = $this->userWithRole('admin');

        $response = Gate::forUser($admin)->inspect('delete', $admin);

        $this->assertTrue($response->denied());
        $this->assertSame('You cannot delete your own account.', $response->message());
    }

    public function test_current_user_cannot_deactivate_themselves(): void
    {
        $admin = $this->userWithRole('admin');

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->set('data.is_active', false)
            ->call('save')
            ->assertHasFormErrors(['is_active']);

        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_non_super_admin_cannot_change_roles_of_a_super_admin_or_promote_self(): void
    {
        $admin = $this->userWithRole('admin');
        $admin->givePermissionTo('manage roles');
        $superAdmin = $this->userWithRole('super-admin');

        $greaterAuthorityErrors = UserAdministration::validateChanges(
            $admin,
            $superAdmin,
            true,
            [Role::findByName('admin')->id],
        );
        $selfPromotionErrors = UserAdministration::validateChanges(
            $admin,
            $admin,
            true,
            [Role::findByName('super-admin')->id],
        );

        $this->assertSame('You are not authorized to change roles for this user.', $greaterAuthorityErrors['roles']);
        $this->assertSame('You are not authorized to change roles for this user.', $selfPromotionErrors['roles']);
        $this->assertTrue($superAdmin->hasRole('super-admin'));
        $this->assertTrue($admin->hasExactRoles(['admin']));
    }

    public function test_current_user_cannot_bulk_delete_themselves(): void
    {
        $admin = $this->userWithRole('admin');

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableBulkAction('delete', [$admin]);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_final_active_super_admin_cannot_be_deleted(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $admin = $this->userWithRole('admin');

        $response = Gate::forUser($admin)->inspect('delete', $superAdmin);

        $this->assertTrue($response->denied());
        $this->assertSame('The final active super-admin cannot be deleted.', $response->message());
    }

    public function test_final_active_super_admin_cannot_be_deactivated(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $admin = $this->userWithRole('admin');

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $superAdmin->getRouteKey()])
            ->set('data.is_active', false)
            ->call('save')
            ->assertHasFormErrors(['is_active']);

        $this->assertTrue($superAdmin->refresh()->is_active);
    }

    public function test_final_active_super_admin_cannot_lose_super_admin_role(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $adminRole = Role::findByName('admin');

        Livewire::actingAs($superAdmin)
            ->test(EditUser::class, ['record' => $superAdmin->getRouteKey()])
            ->set('data.roles', [$adminRole->id])
            ->call('save')
            ->assertHasFormErrors(['roles']);

        $this->assertTrue($superAdmin->refresh()->hasRole('super-admin'));
    }

    public function test_another_super_admin_may_be_modified_when_one_active_super_admin_remains(): void
    {
        $actor = $this->userWithRole('super-admin');
        $target = $this->userWithRole('super-admin');
        $adminRole = Role::findByName('admin');

        Livewire::actingAs($actor)
            ->test(EditUser::class, ['record' => $target->getRouteKey()])
            ->set('data.roles', [$adminRole->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($target->refresh()->hasExactRoles(['admin']));
        $this->assertTrue($actor->refresh()->is_active && $actor->hasRole('super-admin'));
    }

    public function test_database_seeder_creates_authorization_foundation(): void
    {
        Role::query()->delete();
        Permission::query()->delete();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(7, Role::query()->count());
        $this->assertSame(13, Permission::query()->count());
        $this->assertTrue(Role::findByName('super-admin')->hasAllPermissions(Permission::all()));
    }

    public function test_tag_seo_columns_exist_after_migrations(): void
    {
        $this->assertTrue(Schema::hasColumns('tags', ['meta_title', 'meta_description']));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }
}
