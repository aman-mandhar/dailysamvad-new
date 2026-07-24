<?php

namespace Tests\Feature\Authorization;

use App\Enums\PostStatus;
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Support\UserAdministration;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase21RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_reviewer_scope_is_limited_to_assigned_posts(): void
    {
        $reviewer = $this->userWithRole('reviewer');
        $assigned = Post::factory()->create(['reviewed_by' => $reviewer, 'status' => PostStatus::PendingReview]);
        $unassigned = Post::factory()->create(['status' => PostStatus::PendingReview]);
        $privateDraft = Post::factory()->create(['status' => PostStatus::Draft]);

        $this->actingAs($reviewer);

        $this->assertEquals([$assigned->id], PostResource::getEloquentQuery()->pluck('id')->all());
        $this->assertTrue($reviewer->can('review', $assigned));
        $this->assertFalse($reviewer->can('review', $unassigned));
        $this->assertFalse($reviewer->can('view', $privateDraft));
        $this->assertFalse($reviewer->can('publish', $assigned));
    }

    public function test_contributor_scope_and_actions_are_limited_to_own_drafts(): void
    {
        $contributor = $this->userWithRole('contributor');
        $own = Post::factory()->create(['author_id' => $contributor, 'status' => PostStatus::Draft]);
        $foreign = Post::factory()->create(['status' => PostStatus::Draft]);

        $this->actingAs($contributor);

        $this->assertEquals([$own->id], PostResource::getEloquentQuery()->pluck('id')->all());
        $this->assertTrue($contributor->can('update', $own));
        $this->assertTrue($contributor->can('submitForReview', $own));
        $this->assertFalse($contributor->can('update', $foreign));
        $this->assertFalse($contributor->can('publish', $own));
    }

    public function test_seo_and_media_managers_have_narrow_resource_permissions(): void
    {
        $seo = $this->userWithRole('seo-manager');
        $mediaManager = $this->userWithRole('media-manager');
        $post = Post::factory()->create(['status' => PostStatus::PendingReview]);
        $media = Media::query()->create([
            'disk' => 'public', 'path' => 'media/library/test.jpg', 'mime_type' => 'image/jpeg', 'size' => 1,
        ]);

        $this->assertTrue($seo->can('view', $post));
        $this->assertTrue($seo->can('manageSeo', $post));
        $this->assertFalse($seo->can('update', $post));
        $this->assertFalse($seo->can('publish', $post));

        $this->assertTrue($mediaManager->can('viewAny', Media::class));
        $this->assertTrue($mediaManager->can('update', $media));
        $this->assertFalse($mediaManager->can('update', $post));
        $this->actingAs($mediaManager);
        $this->assertTrue(MediaResource::canAccess());
        $this->assertFalse(PostResource::canAccess());
    }

    public function test_super_admin_override_requires_an_active_authenticated_user(): void
    {
        Gate::define('phase-21-system-ability', fn (): bool => false);
        $active = $this->userWithRole('super-admin');
        $inactive = $this->userWithRole('super-admin', false);

        $this->assertTrue(Gate::forUser($active)->allows('phase-21-system-ability'));
        $this->assertFalse(Gate::forUser($inactive)->allows('phase-21-system-ability'));
        $this->assertFalse(Gate::allows('phase-21-system-ability'));
    }

    public function test_role_manager_cannot_assign_permissions_they_do_not_control(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(Permission::findByName('manage roles and permissions'));
        $target = User::factory()->create();
        $editor = Role::findByName('editor');

        $errors = UserAdministration::validateChanges($manager, $target, true, [$editor->id]);

        $this->assertSame('You cannot assign permissions that exceed your own authority.', $errors['roles']);
        $this->assertFalse($target->hasRole('editor'));
    }

    public function test_guest_is_redirected_and_subscriber_is_forbidden_from_filament(): void
    {
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $subscriber = $this->userWithRole('subscriber');
        $this->actingAs($subscriber)->get(route('filament.admin.pages.dashboard'))->assertForbidden();
    }

    private function userWithRole(string $role, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        $user->assignRole($role);

        return $user;
    }
}
