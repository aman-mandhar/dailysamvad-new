<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_login_page_loads_and_valid_subscriber_reaches_dashboard(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Login');
        $user = User::factory()->create(['password' => 'Strong!Password123']);
        $user->assignRole('subscriber');

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Strong!Password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_staff_redirects_to_filament_and_roleless_user_uses_safe_dashboard(): void
    {
        $staff = User::factory()->create(['password' => 'Strong!Password123']);
        $staff->assignRole('reporter');

        $this->post(route('login'), ['email' => $staff->email, 'password' => 'Strong!Password123'])
            ->assertRedirect(route('filament.admin.pages.dashboard'));

        auth()->logout();
        $roleless = User::factory()->create(['password' => 'Strong!Password123']);
        $this->post(route('login'), ['email' => $roleless->email, 'password' => 'Strong!Password123'])
            ->assertRedirect(route('dashboard'));
    }

    #[DataProvider('roleRedirectProvider')]
    public function test_role_redirects_follow_the_central_permission_based_dashboard_rule(string $role, string $route): void
    {
        $user = User::factory()->create(['password' => 'Strong!Password123']);
        $user->assignRole($role);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Strong!Password123',
        ])->assertRedirect(route($route));
    }

    /** @return array<string, array{string, string}> */
    public static function roleRedirectProvider(): array
    {
        return [
            'super admin' => ['super-admin', 'filament.admin.pages.dashboard'],
            'admin' => ['admin', 'filament.admin.pages.dashboard'],
            'editor' => ['editor', 'filament.admin.pages.dashboard'],
            'reporter' => ['reporter', 'filament.admin.pages.dashboard'],
            'author' => ['author', 'filament.admin.pages.dashboard'],
            'subscriber' => ['subscriber', 'dashboard'],
        ];
    }

    public function test_frontend_dashboard_displays_roles_and_handles_roleless_legacy_users_safely(): void
    {
        $subscriber = User::factory()->create();
        $subscriber->assignRole('subscriber');
        $this->actingAs($subscriber)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Current role')
            ->assertSee('Subscriber');

        $roleless = User::factory()->create();
        $this->actingAs($roleless)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No assigned role');
    }

    public function test_multiple_roles_use_effective_permissions_without_redirect_ambiguity(): void
    {
        $user = User::factory()->create(['password' => 'Strong!Password123']);
        $user->assignRole(['subscriber', 'reporter']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Strong!Password123',
        ])->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    public function test_invalid_or_inactive_credentials_are_denied_generically(): void
    {
        $inactive = User::factory()->create(['password' => 'Strong!Password123', 'is_active' => false]);

        $this->post(route('login'), ['email' => $inactive->email, 'password' => 'Strong!Password123'])
            ->assertSessionHasErrors('email');
        $this->post(route('login'), ['email' => 'missing@example.com', 'password' => 'Wrong!Password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $request = LoginRequest::create('/login', 'POST', ['email' => 'limited@example.com']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), ['email' => 'limited@example.com', 'password' => 'Wrong!Password123']);
        }

        $this->assertTrue(RateLimiter::tooManyAttempts($request->throttleKey(), 5));
        $this->post(route('login'), ['email' => 'limited@example.com', 'password' => 'Wrong!Password123'])
            ->assertSessionHasErrors('email');
    }

    public function test_logout_is_post_only_and_invalidates_authentication(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
        $this->get('/logout')->assertNotFound();
    }

    public function test_dashboard_requires_authentication_but_not_verified_email(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified)->get(route('dashboard'))->assertOk();
    }

    public function test_unverified_user_logs_in_and_reaches_role_based_dashboard(): void
    {
        $user = User::factory()->unverified()->create(['password' => 'Strong!Password123']);
        $user->assignRole('subscriber');

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Strong!Password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_email_verification_routes_are_not_registered(): void
    {
        $this->get('/verify-email')->assertNotFound();
        $this->post('/email/verification-notification')->assertNotFound();
    }
}
