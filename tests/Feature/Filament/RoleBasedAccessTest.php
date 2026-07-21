<?php

namespace Tests\Feature\Filament;

use App\Enums\PostStatus;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Tags\TagResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\AdministrativeOverviewWidget;
use App\Filament\Widgets\EditorialOverviewWidget;
use App\Filament\Widgets\OwnPostOverviewWidget;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_dashboard_widgets_follow_permissions_and_own_metrics_do_not_leak(): void
    {
        $admin = $this->userWithRole('admin');
        $editor = $this->userWithRole('editor');
        $reporter = $this->userWithRole('reporter');
        $other = $this->userWithRole('reporter');

        $this->actingAs($admin);
        $this->assertTrue(AdministrativeOverviewWidget::canView());
        $this->assertFalse(EditorialOverviewWidget::canView());
        $this->assertFalse(OwnPostOverviewWidget::canView());

        $this->actingAs($editor);
        $this->assertFalse(AdministrativeOverviewWidget::canView());
        $this->assertTrue(EditorialOverviewWidget::canView());
        $this->assertFalse(OwnPostOverviewWidget::canView());

        Post::factory()->create(['author_id' => $reporter, 'status' => PostStatus::Draft, 'views_count' => 7]);
        Post::factory()->create(['author_id' => $reporter, 'status' => PostStatus::Published, 'views_count' => 11]);
        Post::factory()->create(['author_id' => $other, 'status' => PostStatus::Draft, 'views_count' => 1000]);

        $this->actingAs($reporter);
        $this->assertTrue(OwnPostOverviewWidget::canView());
        $metrics = app(OwnPostOverviewWidget::class)->metrics();
        $this->assertSame(1, $metrics['My drafts']);
        $this->assertSame(1, $metrics['My published posts']);
        $this->assertSame(18, $metrics['My total views']);
    }

    public function test_post_list_search_filters_and_global_search_keep_ownership_scope(): void
    {
        $reporter = $this->userWithRole('reporter');
        $other = $this->userWithRole('reporter');
        $own = Post::factory()->create(['author_id' => $reporter, 'title' => 'Scoped report', 'status' => PostStatus::Draft]);
        $foreign = Post::factory()->create(['author_id' => $other, 'title' => 'Scoped report secret', 'status' => PostStatus::Draft]);

        Livewire::actingAs($reporter)
            ->test(ListPosts::class)
            ->searchTable('Scoped report')
            ->filterTable('status', PostStatus::Draft->value)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$foreign]);

        $this->actingAs($reporter);
        $this->assertEqualsCanonicalizing([$own->id], PostResource::getEloquentQuery()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$own->id], PostResource::getGlobalSearchEloquentQuery()->pluck('id')->all());
    }

    public function test_direct_post_urls_are_policy_protected_for_reporter_and_author(): void
    {
        foreach (['reporter', 'author'] as $role) {
            $user = $this->userWithRole($role);
            $other = $this->userWithRole('reporter');
            $own = Post::factory()->create(['author_id' => $user, 'status' => PostStatus::Draft]);
            $foreign = Post::factory()->create(['author_id' => $other, 'status' => PostStatus::Draft]);

            $this->actingAs($user)->get(PostResource::getUrl('edit', ['record' => $own]))->assertOk();
            $this->actingAs($user)->get(PostResource::getUrl('edit', ['record' => $foreign]))->assertForbidden();
        }
    }

    public function test_reporter_creation_enforces_owner_and_rejects_publish_tampering(): void
    {
        $reporter = $this->userWithRole('reporter');
        $other = $this->userWithRole('reporter');
        $category = Category::factory()->create();

        Livewire::actingAs($reporter)
            ->test(CreatePost::class)
            ->fillForm($this->postData($category, [
                'slug' => 'reporter-owned-post',
                'author_id' => $other->id,
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('posts', [
            'slug' => 'reporter-owned-post',
            'author_id' => $reporter->id,
            'status' => PostStatus::Draft->value,
        ]);

        Livewire::actingAs($reporter)
            ->test(CreatePost::class)
            ->fillForm($this->postData($category, [
                'slug' => 'reporter-publish-attempt',
                'status' => PostStatus::Published->value,
            ]))
            ->call('create')
            ->assertHasFormErrors(['status']);

        $this->assertDatabaseMissing('posts', ['slug' => 'reporter-publish-attempt']);
    }

    public function test_non_administrators_are_denied_user_taxonomy_and_direct_urls(): void
    {
        $reporter = $this->userWithRole('reporter');
        $target = User::factory()->create();

        $this->actingAs($reporter)->get(UserResource::getUrl('index'))->assertForbidden();
        $this->actingAs($reporter)->get(UserResource::getUrl('edit', ['record' => $target]))->assertForbidden();
        $this->actingAs($reporter)->get(CategoryResource::getUrl('index'))->assertForbidden();
        $this->actingAs($reporter)->get(TagResource::getUrl('index'))->assertForbidden();
    }

    public function test_admin_cannot_edit_or_delete_a_super_admin_but_super_admin_can(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(UserResource::getUrl('edit', ['record' => $superAdmin]))->assertForbidden();
        $this->assertFalse($admin->can('delete', $superAdmin));
        $this->assertTrue($superAdmin->can('update', $admin));
    }

    public function test_admin_can_open_the_panel_dashboard_while_subscriber_is_denied(): void
    {
        $admin = $this->userWithRole('admin');
        $subscriber = $this->userWithRole('subscriber');

        $this->actingAs($admin)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertOk();
        $this->actingAs($subscriber)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** @param array<string, mixed> $overrides */
    private function postData(Category $category, array $overrides = []): array
    {
        return array_replace([
            'title' => 'Reporter owned post',
            'slug' => 'reporter-owned-post',
            'content' => '<p>Reporter content.</p>',
            'language' => 'hi',
            'status' => PostStatus::Draft->value,
            'categories' => [$category->id],
            'primary_category_id' => $category->id,
        ], $overrides);
    }
}
