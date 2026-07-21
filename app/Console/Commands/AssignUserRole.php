<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class AssignUserRole extends Command
{
    protected $signature = 'app:assign-role
        {identifier : Exact user email address or numeric user ID}
        {role : Exact existing role name}';

    protected $description = 'Assign an existing role to an explicitly identified user without removing current roles';

    public function handle(): int
    {
        $identifier = trim((string) $this->argument('identifier'));
        $roleName = trim((string) $this->argument('role'));

        $user = ctype_digit($identifier)
            ? User::query()->whereKey((int) $identifier)->first()
            : User::query()->where('email', $identifier)->first();

        if (! $user) {
            $this->error('No user matches that exact identifier.');

            return self::FAILURE;
        }

        $role = Role::query()
            ->where('guard_name', 'web')
            ->where('name', $roleName)
            ->first();

        if (! $role) {
            $this->error('No role with that exact name exists for the web guard.');

            return self::FAILURE;
        }

        $currentRoles = $user->getRoleNames()->join(', ') ?: 'none';
        $this->table(
            ['User ID', 'Email', 'Current roles', 'Requested role'],
            [[$user->getKey(), $user->email, $currentRoles, $role->name]],
        );

        if ($user->hasRole($role)) {
            $this->info('The user already has this role; no changes were made.');

            return self::SUCCESS;
        }

        if ($role->name === 'super-admin'
            && ! $this->confirm('Assign Super Admin to this explicitly identified user?')) {
            $this->warn('Role assignment cancelled.');

            return self::FAILURE;
        }

        $user->assignRole($role);

        Log::notice('User role assigned by an authorized console operator.', [
            'user_id' => $user->getKey(),
            'role' => $role->name,
        ]);

        $this->info("Role [{$role->name}] assigned. Existing roles were preserved.");

        return self::SUCCESS;
    }
}
