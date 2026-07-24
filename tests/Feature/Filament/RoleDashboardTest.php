<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\AdminDashboard;
use App\Filament\Pages\AnalyticsDashboard;
use App\Filament\Pages\EditorDashboard;
use App\Filament\Pages\MediaDashboard;
use App\Filament\Pages\ReporterDashboard;
use App\Filament\Pages\ReviewerDashboard;
use App\Filament\Pages\SeoDashboard;
use App\Filament\Pages\SuperAdminDashboard;
use App\Models\Post;
use App\Models\User;
use App\Services\DashboardMetrics;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_each_staff_workspace_is_permission_gated_and_subscriber_is_excluded(): void
    {
        $cases = [
            'super-admin' => SuperAdminDashboard::class,
            'admin' => AdminDashboard::class,
            'editor' => EditorDashboard::class,
            'reviewer' => ReviewerDashboard::class,
            'reporter' => ReporterDashboard::class,
            'seo-manager' => SeoDashboard::class,
            'media-manager' => MediaDashboard::class,
            'analytics-manager' => AnalyticsDashboard::class,
            'contributor' => \App\Filament\Pages\ContributorDashboard::class,
        ];

        foreach ($cases as $role => $page) {
            $user = $this->userWithRole($role);
            $this->actingAs($user);
            $this->assertTrue($page::canAccess(), "{$role} workspace should be accessible");
            $this->get($page::getUrl())->assertOk();
        }

        $subscriber = $this->userWithRole('subscriber');
        $this->actingAs($subscriber)->get(SuperAdminDashboard::getUrl())->assertForbidden();
    }

    public function test_reporter_and_reviewer_metrics_are_scoped(): void
    {
        $reporter = $this->userWithRole('reporter');
        $other = $this->userWithRole('reporter');
        $reviewer = $this->userWithRole('reviewer');
        Post::factory()->create(['author_id' => $reporter, 'status' => 'draft', 'views_count' => 4]);
        Post::factory()->create(['author_id' => $other, 'status' => 'draft', 'views_count' => 900]);
        Post::factory()->create(['author_id' => $other, 'reviewed_by' => $reviewer, 'status' => 'pending_review']);
        Post::factory()->create(['author_id' => $reporter, 'status' => 'draft', 'views_count' => 8]);

        $this->actingAs($reporter);
        $own = app(DashboardMetrics::class)->ownSummary($reporter);
        $this->assertSame(2, $own['drafts']);
        $this->assertSame(12, $own['views']);

        $assigned = app(DashboardMetrics::class)->editorialSummary($reviewer);
        $this->assertSame(1, $assigned['pending_review']);
    }

    public function test_direct_unpermitted_workspace_is_forbidden(): void
    {
        $reporter = $this->userWithRole('reporter');
        $this->actingAs($reporter)->get(AdminDashboard::getUrl())->assertForbidden();
        $this->actingAs($reporter)->get(SeoDashboard::getUrl())->assertForbidden();
    }

    public function test_workspace_markup_is_responsive_accessible_and_has_empty_activity_state(): void
    {
        $reporter = $this->userWithRole('reporter');

        $response = $this->actingAs($reporter)->get(\App\Filament\Pages\ReporterDashboard::getUrl());

        $response->assertOk()
            ->assertSee('aria-labelledby="dashboard-heading"', false)
            ->assertSee('grid-cols-1', false)
            ->assertSee('sm:grid-cols-2', false)
            ->assertSee('Recent workflow activity')
            ->assertSee('No workflow activity is available in your authorized scope.');
    }

    public function test_workspace_markup_is_responsive_accessible_and_has_loading_and_empty_states(): void
    {
        $reporter = $this->userWithRole('reporter');

        $this->actingAs($reporter)
            ->get(ReporterDashboard::getUrl())
            ->assertOk()
            ->assertSee('aria-labelledby="dashboard-heading"', false)
            ->assertSee('sm:grid-cols-2', false)
            ->assertSee('xl:grid-cols-4', false)
            ->assertSee('aria-label="Recent workflow activity"', false)
            ->assertSee('wire:loading', false);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }
}
