<?php
namespace App\Filament\Pages;
use App\Models\User;
use App\Services\DashboardMetrics;
class ReviewerDashboard extends RoleDashboard
{
    protected static ?string $title = 'Reviewer Workspace';
    protected static ?string $navigationLabel = 'Review Queue';
    public static function canAccess(): bool { $u = auth()->user(); return (bool) ($u?->can('review posts') && $u?->can('view assigned posts') && ! $u->can('view all posts')); }
    protected static function metrics(DashboardMetrics $metrics, User $user): array { return $metrics->editorialSummary($user); }
}
