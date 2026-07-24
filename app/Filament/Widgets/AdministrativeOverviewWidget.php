<?php

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\DashboardMetrics;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdministrativeOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->can('manage users') ?? false;
    }

    /** @return array<string, int> */
    public function metrics(): array
    {
        $summary = app(DashboardMetrics::class)->adminSummary(auth()->user());
        return ['Pending review' => $summary['pending_review'], 'Approved posts' => $summary['approved'], 'Scheduled posts' => $summary['scheduled'], 'Published today' => $summary['published_today'], 'Users' => $summary['users'], 'Categories' => $summary['categories'], 'Tags' => $summary['tags']];
    }

    protected function getStats(): array
    {
        return collect($this->metrics())->map(fn (int $value, string $label): Stat => Stat::make($label, number_format($value)))->values()->all();
    }
}
