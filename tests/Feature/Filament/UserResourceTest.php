<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_user_policy_uses_manage_users_permission(): void
    {
        $target = User::factory()->create();
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $this->assertTrue(Gate::forUser($this->admin)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $target));
        $this->assertTrue(Gate::forUser($this->admin)->allows('create', User::class));
        $this->assertTrue(Gate::forUser($this->admin)->allows('update', $target));
        $this->assertTrue(Gate::forUser($this->admin)->allows('delete', $target));
        $this->assertFalse(Gate::forUser($editor)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($editor)->allows('update', $target));
    }

    public function test_authorized_user_can_open_user_resource_pages(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin)
            ->get(UserResource::getUrl('index'))
            ->assertOk();
        $this->actingAs($this->admin)
            ->get(UserResource::getUrl('create'))
            ->assertOk();
        $this->actingAs($this->admin)
            ->get(UserResource::getUrl('edit', ['record' => $target]))
            ->assertOk();
    }

    public function test_user_without_manage_users_permission_cannot_open_resource(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $this->actingAs($editor)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_create_user_requires_a_strong_confirmed_password(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Weak Password User',
                'email' => 'weak@example.com',
                'username' => 'weak-user',
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_create_user_supports_multiple_roles(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $roleIds = Role::query()
            ->whereIn('name', ['editor', 'reporter'])
            ->pluck('id')
            ->all();

        Livewire::actingAs($superAdmin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Editorial User',
                'email' => 'editorial@example.com',
                'username' => 'editorial-user',
                'password' => 'Strong!Password123',
                'password_confirmation' => 'Strong!Password123',
                'is_active' => true,
                'roles' => $roleIds,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'editorial@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('Strong!Password123', $user->password));
        $this->assertTrue($user->hasAllRoles(['editor', 'reporter']));
    }

    public function test_password_is_optional_when_editing_a_user(): void
    {
        $target = User::factory()->create(['password' => 'Existing!Password123']);
        $originalPassword = $target->password;

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $target->getRouteKey()])
            ->fillForm([
                'name' => 'Updated Name',
                'password' => null,
                'password_confirmation' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $target->refresh();

        $this->assertSame('Updated Name', $target->name);
        $this->assertSame($originalPassword, $target->password);
    }
}
