<?php
namespace App\Filament\Pages;
use App\Models\User;
use App\Services\DashboardMetrics;
class AdminDashboard extends RoleDashboard
{
    protected static ?string $title = 'Admin Workspace';
    protected static ?string $navigationLabel = 'Administration';
    public static function canAccess(): bool { $user = auth()->user(); return (bool) ($user?->can('manage users') && ! $user->can('manage permissions')); }
    protected static function metrics(DashboardMetrics $metrics, User $user): array { return $metrics->adminSummary($user); }
}
