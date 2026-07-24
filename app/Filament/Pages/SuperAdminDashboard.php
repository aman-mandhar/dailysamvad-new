<?php
namespace App\Filament\Pages;
use App\Models\User;
use App\Services\DashboardMetrics;
class SuperAdminDashboard extends RoleDashboard
{
    protected static ?string $title = 'Super Admin Workspace';
    protected static ?string $navigationLabel = 'Super Admin';
    public static function canAccess(): bool { return auth()->user()?->can('manage permissions') ?? false; }
    protected static function metrics(DashboardMetrics $metrics, User $user): array { return $metrics->adminSummary($user); }
    protected static function getDescription(): string { return 'System-wide authorization, publishing, taxonomy, and content health.'; }
}
