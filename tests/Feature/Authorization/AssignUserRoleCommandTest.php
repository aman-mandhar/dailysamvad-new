<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AssignUserRoleCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_command_requires_an_exact_existing_user_and_role(): void
    {
        $this->artisan('app:assign-role', ['identifier' => 'missing@example.com', 'role' => 'admin'])
            ->expectsOutput('No user matches that exact identifier.')
            ->assertFailed();

        $user = User::factory()->create();
        $this->artisan('app:assign-role', ['identifier' => $user->email, 'role' => 'missing-role'])
            ->expectsOutput('No role with that exact name exists for the web guard.')
            ->assertFailed();

        $this->assertTrue($user->fresh()->roles->isEmpty());
    }

    public function test_super_admin_assignment_requires_confirmation_and_preserves_existing_roles(): void
    {
        Log::spy();
        $user = User::factory()->create();
        $user->assignRole('subscriber');

        $this->artisan('app:assign-role', ['identifier' => $user->email, 'role' => 'super-admin'])
            ->expectsConfirmation('Assign Super Admin to this explicitly identified user?', 'no')
            ->expectsOutput('Role assignment cancelled.')
            ->assertFailed();
        $this->assertFalse($user->fresh()->hasRole('super-admin'));

        $this->artisan('app:assign-role', ['identifier' => (string) $user->id, 'role' => 'super-admin'])
            ->expectsConfirmation('Assign Super Admin to this explicitly identified user?', 'yes')
            ->expectsOutput('Role [super-admin] assigned. Existing roles were preserved.')
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->hasAllRoles(['subscriber', 'super-admin']));
        Log::shouldHaveReceived('notice')->once()->with(
            'User role assigned by an authorized console operator.',
            ['user_id' => $user->id, 'role' => 'super-admin'],
        );
    }

    public function test_repeated_assignment_is_idempotent(): void
    {
        $user = User::factory()->create();
        $user->assignRole('reporter');

        $this->artisan('app:assign-role', ['identifier' => $user->email, 'role' => 'reporter'])
            ->expectsOutput('The user already has this role; no changes were made.')
            ->assertSuccessful();

        $this->assertCount(1, $user->fresh()->roles);
    }
}
