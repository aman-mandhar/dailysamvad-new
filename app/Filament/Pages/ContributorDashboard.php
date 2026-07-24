<?php
namespace App\Filament\Pages;
use App\Models\User;
use App\Services\DashboardMetrics;
class ContributorDashboard extends RoleDashboard
{
    protected static ?string $title = 'Contributor Workspace';
    protected static ?string $navigationLabel = 'Contributions';
    public static function canAccess(): bool { $u = auth()->user(); return (bool) ($u?->can('create posts') && $u?->can('view own posts') && ! $u->can('view own analytics') && ! $u->can('review posts')); }
    protected static function metrics(DashboardMetrics $metrics, User $user): array { return $metrics->ownSummary($user); }
}
