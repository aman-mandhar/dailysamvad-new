<?php

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Services\DashboardMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OwnPostOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('view own posts') && ! $user->can('view all posts');
    }

    /** @return array<string, int> */
    public function metrics(): array
    {
        $summary = app(DashboardMetrics::class)->ownSummary(auth()->user());
        return ['My drafts' => $summary['drafts'], 'My submitted posts' => $summary['pending_review'], 'Corrections required' => $summary['changes_requested'], 'My published posts' => $summary['published'], 'My total views' => $summary['views']];
    }

    protected function getStats(): array
    {
        return collect($this->metrics())->map(fn (int $value, string $label): Stat => Stat::make($label, number_format($value)))->values()->all();
    }
}
