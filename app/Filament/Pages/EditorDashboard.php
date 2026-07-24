<?php
namespace App\Filament\Pages;
use App\Models\User;
use App\Services\DashboardMetrics;
class EditorDashboard extends RoleDashboard
{
    protected static ?string $title = 'Editor Workspace';
    protected static ?string $navigationLabel = 'Editorial';
    public static function canAccess(): bool { return (bool) (auth()->user()?->can('review posts') && auth()->user()?->can('view all posts')); }
    protected static function metrics(DashboardMetrics $metrics, User $user): array { return $metrics->editorialSummary($user); }
}
