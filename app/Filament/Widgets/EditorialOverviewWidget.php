<?php

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Models\Post;
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
        return [
            'Pending review' => Post::query()->where('status', PostStatus::PendingReview)->count(),
            'Corrections required' => Post::query()->where('status', PostStatus::Rejected)->count(),
            'Scheduled posts' => Post::query()->where('status', PostStatus::Scheduled)->count(),
            'Published today' => Post::query()->where('status', PostStatus::Published)->whereDate('published_at', today())->count(),
        ];
    }

    protected function getStats(): array
    {
        return collect($this->metrics())->map(fn (int $value, string $label): Stat => Stat::make($label, number_format($value)))->values()->all();
    }
}
