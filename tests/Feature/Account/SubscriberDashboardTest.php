<?php

namespace Tests\Feature\Account;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Models\PostBookmark;
use App\Models\PostVisit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriberDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_guest_is_redirected_and_subscriber_can_access_account_but_not_filament(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $subscriber = $this->subscriber();
        $this->actingAs($subscriber)->get(route('dashboard'))->assertOk()->assertSee($subscriber->refcode);
        $this->assertFalse($subscriber->canAccessPanel(Filament::getPanel('admin')));
        $this->actingAs($subscriber)->get(PostResource::getUrl('index'))->assertForbidden();
    }

    public function test_unverified_users_can_use_subscriber_endpoints_but_suspended_users_cannot(): void
    {
        $unverified = $this->subscriber(['email_verified_at' => null]);
        $this->actingAs($unverified)
            ->get(route('account.profile.edit'))
            ->assertOk();

        $suspended = $this->subscriber(['is_active' => false]);
        $this->actingAs($suspended)
            ->get(route('account.profile.edit'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_profile_updates_only_allowed_fields_and_preserves_sensitive_account_data(): void
    {
        $referrer = $this->subscriber();
        $user = $this->subscriber(['ref_id' => $referrer->id]);
        $code = $user->refcode;
        $password = $user->password;

        $this->actingAs($user)->patch(route('account.profile.update'), [
            'name' => 'Updated Subscriber', 'mobile_number' => '1234567890', 'preferred_language' => 'pa',
            'email' => 'attacker@example.test', 'refcode' => 'ATTACK', 'ref_id' => null,
            'is_active' => false, 'roles' => ['super-admin'], 'email_verified_at' => null,
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('Updated Subscriber', $user->name);
        $this->assertSame($code, $user->refcode);
        $this->assertSame($referrer->id, $user->ref_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasExactRoles('subscriber'));
        $this->assertSame($password, $user->password);
        $this->assertNotSame('attacker@example.test', $user->email);
    }

    public function test_password_change_requires_current_password_and_hashes_new_password(): void
    {
        $user = $this->subscriber(['password' => 'Original1!Password']);
        $payload = ['current_password' => 'wrong', 'password' => 'Replacement1!Password', 'password_confirmation' => 'Replacement1!Password'];
        $this->actingAs($user)->put(route('account.password.update'), $payload)->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('Original1!Password', $user->fresh()->password));

        $payload['current_password'] = 'Original1!Password';
        $this->actingAs($user)->put(route('account.password.update'), $payload)->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Replacement1!Password', $user->fresh()->password));
        $this->assertFalse(Hash::check('Original1!Password', $user->fresh()->password));
    }

    public function test_referrals_are_direct_private_and_paginated_without_contact_data(): void
    {
        $user = $this->subscriber();
        $direct = $this->subscriber(['ref_id' => $user->id, 'name' => 'Private Person', 'email' => 'private@example.test']);
        $other = $this->subscriber();
        $this->subscriber(['ref_id' => $other->id, 'name' => 'Other Referral']);

        $this->actingAs($user)->get(route('account.referrals'))
            ->assertOk()->assertSee($user->refcode)->assertSee('P*************')
            ->assertDontSee($direct->email)->assertDontSee('Other Referral');
    }

    public function test_bookmarks_enforce_owner_published_visibility_and_do_not_change_views(): void
    {
        $user = $this->subscriber();
        $other = $this->subscriber();
        $published = Post::factory()->published()->create(['views_count' => 17]);
        $draft = Post::factory()->create(['status' => PostStatus::Draft]);

        $this->actingAs($user)->post(route('account.saved.store', $published))->assertRedirect();
        $this->actingAs($user)->post(route('account.saved.store', $published))->assertRedirect();
        $this->assertSame(1, PostBookmark::where('user_id', $user->id)->count());
        $this->assertSame(17, $published->fresh()->views_count);
        $this->actingAs($user)->post(route('account.saved.store', $draft))->assertNotFound();

        $foreign = PostBookmark::create(['user_id' => $other->id, 'post_id' => $published->id]);
        $this->actingAs($user)->delete(route('account.saved.destroy', $foreign))->assertForbidden();
        $this->assertDatabaseHas('post_bookmarks', ['id' => $foreign->id]);
    }

    public function test_history_deduplicates_own_visits_and_hides_analytics_fields(): void
    {
        $user = $this->subscriber();
        $other = $this->subscriber();
        $post = Post::factory()->published()->create(['title' => 'My history article']);
        $otherPost = Post::factory()->published()->create(['title' => 'Other private history']);
        PostVisit::factory()->count(2)->create(['visitor_id' => $user, 'post_id' => $post, 'ip_address' => '192.0.2.1']);
        PostVisit::factory()->create(['visitor_id' => $other, 'post_id' => $otherPost]);
        PostVisit::factory()->create(['visitor_id' => null, 'post_id' => $otherPost]);

        $this->actingAs($user)->get(route('account.history'))->assertOk()
            ->assertSee('My history article')->assertDontSee('Other private history')->assertDontSee('192.0.2.1');
    }

    public function test_notifications_are_owned_escaped_and_read_actions_cannot_cross_users(): void
    {
        $user = $this->subscriber();
        $other = $this->subscriber();
        $ownId = $this->notification($user, '<script>alert(1)</script> Account notice');
        $foreignId = $this->notification($other, 'Foreign notice');

        $this->actingAs($user)->get(route('account.notifications.index'))->assertOk()
            ->assertSee('alert(1) Account notice')->assertDontSee('<script>', false)->assertDontSee('Foreign notice');
        $this->actingAs($user)->patch(route('account.notifications.read', $foreignId))->assertNotFound();
        $this->actingAs($user)->patch(route('account.notifications.read', $ownId))->assertRedirect();
        $this->assertNotNull(DB::table('notifications')->where('id', $ownId)->value('read_at'));
    }

    private function subscriber(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('subscriber');

        return $user;
    }

    private function notification(User $user, string $message): string
    {
        $id = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $id, 'type' => 'Tests\\AccountNotification', 'notifiable_type' => User::class,
            'notifiable_id' => $user->id, 'data' => json_encode(['message' => $message]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }
}
