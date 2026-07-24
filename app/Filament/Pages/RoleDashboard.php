<?php

namespace App\Filament\Pages;

use App\Services\DashboardMetrics;
use App\Services\CacheQueryService;
use Filament\Pages\Page;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Users\UserResource;

abstract class RoleDashboard extends Page
{
    protected string $view = 'filament.pages.role-dashboard';
    protected static string|\UnitEnum|null $navigationGroup = 'Workspaces';
    protected static ?int $navigationSort = 1;
    protected ?array $cachedDashboardData = null;

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    /** @return array<string, mixed> */
    public function dashboardData(): array
    {
        if ($this->cachedDashboardData !== null) return $this->cachedDashboardData;
        $user = auth()->user();
        $metrics = app(DashboardMetrics::class);

        $cachedMetrics = app(CacheQueryService::class)->remember('dashboard', 'metrics', static::class, $user->getKey(), (int) config('cache_architecture.ttls.very_short', 60), fn (): array => static::metrics($metrics, $user));
        return $this->cachedDashboardData = [
            'heading' => static::getTitle(),
            'description' => static::getDescription(),
            'metrics' => $cachedMetrics,
            'activity' => $metrics->recentActivity($user),
            'actions' => static::actions($user),
        ];
    }

    abstract protected static function metrics(DashboardMetrics $metrics, \App\Models\User $user): array;

    /** @return array<int, array{label: string, url: string}> */
    protected static function actions(\App\Models\User $user): array
    {
        $actions = [];
        if ($user->can('create posts')) $actions[] = ['label' => 'Create post', 'url' => PostResource::getUrl('create')];
        if ($user->can('view posts')) $actions[] = ['label' => 'Open content', 'url' => PostResource::getUrl('index')];
        if ($user->can('view media') || $user->can('manage media')) $actions[] = ['label' => 'Open media', 'url' => MediaResource::getUrl('index')];
        if ($user->can('manage users')) $actions[] = ['label' => 'Manage users', 'url' => UserResource::getUrl('index')];
        return $actions;
    }

    protected static function getDescription(): string { return 'Use the tools and data authorized for your workspace.'; }

    protected function getViewData(): array { return $this->dashboardData(); }
}
