<?php
namespace App\Filament\Pages;
use App\Models\User;
use App\Services\DashboardMetrics;
class ReporterDashboard extends RoleDashboard
{
    protected static ?string $title = 'Reporter Workspace';
    protected static ?string $navigationLabel = 'My Reporting';
    public static function canAccess(): bool { $u = auth()->user(); return (bool) ($u?->can('view own posts') && $u->can('view own analytics') && ! $u->can('view all posts') && ! $u->can('review posts')); }
    protected static function metrics(DashboardMetrics $metrics, User $user): array { return $metrics->ownSummary($user); }
}
