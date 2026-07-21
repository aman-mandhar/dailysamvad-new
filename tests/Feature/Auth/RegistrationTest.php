<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_registration_page_loads(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Create Account');
    }

    public function test_valid_registration_assigns_only_subscriber_and_resolves_referral(): void
    {
        Event::fake([Registered::class]);
        $referrer = User::factory()->create();
        $existingUsers = User::count();

        $response = $this->post(route('register'), [
            'name' => 'New Subscriber',
            'email' => 'Subscriber@Example.com',
            'password' => 'Strong!Password123',
            'password_confirmation' => 'Strong!Password123',
            'referral_code' => strtolower($referrer->refcode),
            'role' => 'super-admin',
            'roles' => ['admin'],
            'permissions' => ['manage users'],
            'ref_id' => 999999,
            'refcode' => 'DSATTACK01',
            'email_verified_at' => now(),
        ]);

        $user = User::query()->where('email', 'subscriber@example.com')->firstOrFail();
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($existingUsers + 1, User::count());
        $this->assertTrue($user->hasExactRoles('subscriber'));
        $this->assertSame($referrer->getKey(), $user->ref_id);
        $this->assertMatchesRegularExpression('/^DS[A-Z0-9]{8}$/', $user->refcode);
        $this->assertNotSame('DSATTACK01', $user->refcode);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('Strong!Password123', $user->password));
        $this->assertFalse($user->can('access admin panel'));
        Event::assertDispatched(Registered::class);
    }

    public function test_invalid_referral_is_ignored_safely(): void
    {
        $this->post(route('register'), [
            'name' => 'Direct User',
            'email' => 'direct@example.com',
            'password' => 'Strong!Password123',
            'password_confirmation' => 'Strong!Password123',
            'referral_code' => 'DSNOTFOUND',
        ])->assertRedirect(route('dashboard'));

        $this->assertNull(User::query()->where('email', 'direct@example.com')->value('ref_id'));
    }

    public function test_registration_without_a_referral_preserves_existing_users_and_uses_no_arbitrary_default_referrer(): void
    {
        $existing = User::factory()->create([
            'name' => 'Existing Imported User',
            'old_wp_id' => 42,
        ]);
        $snapshot = $existing->fresh()->getAttributes();

        $this->post(route('register'), [
            'name' => 'Direct Subscriber',
            'email' => 'direct-subscriber@example.com',
            'password' => 'Strong!Password123',
            'password_confirmation' => 'Strong!Password123',
        ])->assertRedirect(route('dashboard'));

        $registered = User::query()->where('email', 'direct-subscriber@example.com')->firstOrFail();
        $this->assertNull($registered->ref_id);
        $this->assertTrue($registered->hasExactRoles('subscriber'));
        $this->assertSame($snapshot, $existing->fresh()->getAttributes());
    }

    public function test_duplicate_email_is_rejected_without_disturbing_existing_user(): void
    {
        $existing = User::factory()->create(['email' => 'existing@example.com']);

        $this->from(route('register'))->post(route('register'), [
            'name' => 'Duplicate',
            'email' => 'existing@example.com',
            'password' => 'Strong!Password123',
            'password_confirmation' => 'Strong!Password123',
        ])->assertRedirect(route('register'))->assertSessionHasErrors('email');

        $this->assertSame(1, User::query()->where('email', $existing->email)->count());
    }
}
