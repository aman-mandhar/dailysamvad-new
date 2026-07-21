<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_loads_and_request_is_generic(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->get(route('password.request'))->assertOk()->assertSee('Forgot Password');
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
        $this->post(route('password.email'), ['email' => 'missing@example.com'])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_valid_token_resets_password_and_invalid_token_fails(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'Old!Password123']);
        $this->post(route('password.email'), ['email' => $user->email]);
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'New!Password123',
            'password_confirmation' => 'New!Password123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('New!Password123', $user->fresh()->password));
        $this->assertFalse(Hash::check('Old!Password123', $user->fresh()->password));

        $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'Other!Password123',
            'password_confirmation' => 'Other!Password123',
        ])->assertSessionHasErrors('email');
    }
}
