<?php
namespace App\Filament\Pages;
use App\Models\User;
use App\Services\DashboardMetrics;
class SeoDashboard extends RoleDashboard
{
    protected static ?string $title = 'SEO Workspace';
    protected static ?string $navigationLabel = 'SEO';
    public static function canAccess(): bool { return (bool) (auth()->user()?->can('view seo') || auth()->user()?->can('manage seo')); }
    protected static function metrics(DashboardMetrics $metrics, User $user): array { return $metrics->seoSummary($user); }
}
