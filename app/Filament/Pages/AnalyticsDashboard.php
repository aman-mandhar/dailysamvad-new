<?php
namespace App\Filament\Pages;
use App\Models\User;
use App\Services\DashboardMetrics;
class AnalyticsDashboard extends RoleDashboard
{
    protected static ?string $title = 'Analytics Workspace';
    protected static ?string $navigationLabel = 'Analytics';
    public static function canAccess(): bool { $u = auth()->user(); return (bool) ($u?->can('view all analytics') || $u?->can('view editorial analytics') || $u?->can('view own analytics')); }
    protected static function metrics(DashboardMetrics $metrics, User $user): array { return $metrics->analyticsSummary($user); }
}
