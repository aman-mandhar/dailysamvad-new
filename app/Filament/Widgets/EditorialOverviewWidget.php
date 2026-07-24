<?php

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Services\DashboardMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EditorialOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('review posts') && ! $user->can('manage users');
    }

    /** @return array<string, int> */
    public function metrics(): array
    {
        $summary = app(DashboardMetrics::class)->editorialSummary(auth()->user());
        return ['Pending review' => $summary['pending_review'], 'Corrections required' => $summary['changes_requested'], 'Approved posts' => $summary['approved'], 'Scheduled posts' => $summary['scheduled'], 'Published today' => $summary['published_today']];
    }

    protected function getStats(): array
    {
        return collect($this->metrics())->map(fn (int $value, string $label): Stat => Stat::make($label, number_format($value)))->values()->all();
    }
}
