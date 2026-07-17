<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_user_defaults_to_active(): void
    {
        $user = User::query()->create([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => 'password',
        ]);

        $this->assertTrue($user->refresh()->is_active);
    }

    public function test_profile_fields_may_be_null(): void
    {
        $user = User::factory()->create($this->nullableProfileFields());

        foreach (array_keys($this->nullableProfileFields()) as $field) {
            $this->assertNull($user->{$field});
        }
    }

    public function test_old_wp_id_must_be_unique_when_present(): void
    {
        User::factory()->create(['old_wp_id' => 100]);

        $this->expectException(QueryException::class);

        User::factory()->create(['old_wp_id' => 100]);
    }

    public function test_username_must_be_unique_when_present(): void
    {
        User::factory()->create(['username' => 'unique-user']);

        $this->expectException(QueryException::class);

        User::factory()->create(['username' => 'unique-user']);
    }

    public function test_slug_must_be_unique_when_present(): void
    {
        User::factory()->create(['slug' => 'unique-user']);

        $this->expectException(QueryException::class);

        User::factory()->create(['slug' => 'unique-user']);
    }

    public function test_multiple_null_old_wp_id_values_are_allowed(): void
    {
        User::factory()->count(2)->create(['old_wp_id' => null]);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_multiple_null_username_values_are_allowed(): void
    {
        User::factory()->count(2)->create(['username' => null]);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_multiple_null_slug_values_are_allowed(): void
    {
        User::factory()->count(2)->create(['slug' => null]);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_email_and_password_authentication_remains_functional(): void
    {
        $user = User::factory()->create([
            'email' => 'authenticate@example.com',
            'password' => 'secret-password',
        ]);

        $this->assertTrue(Auth::attempt([
            'email' => 'authenticate@example.com',
            'password' => 'secret-password',
        ]));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @return array<string, null>
     */
    private function nullableProfileFields(): array
    {
        return [
            'old_wp_id' => null,
            'username' => null,
            'slug' => null,
            'mobile_number' => null,
            'avatar_path' => null,
            'bio' => null,
            'designation' => null,
            'facebook_url' => null,
            'x_url' => null,
            'instagram_url' => null,
            'youtube_url' => null,
            'last_login_at' => null,
        ];
    }
}
