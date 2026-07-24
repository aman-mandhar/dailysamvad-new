<?php
namespace App\Filament\Pages;
use App\Models\User;
use App\Services\DashboardMetrics;
class MediaDashboard extends RoleDashboard
{
    protected static ?string $title = 'Media Workspace';
    protected static ?string $navigationLabel = 'Media';
    public static function canAccess(): bool { $u = auth()->user(); return (bool) ($u?->can('view media') || $u?->can('manage media')); }
    protected static function metrics(DashboardMetrics $metrics, User $user): array { return $metrics->mediaSummary($user); }
}
