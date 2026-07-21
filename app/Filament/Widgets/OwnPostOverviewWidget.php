<?php

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Models\Post;
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
        $query = Post::query()->where('author_id', auth()->id());

        return [
            'My drafts' => (clone $query)->where('status', PostStatus::Draft)->count(),
            'My submitted posts' => (clone $query)->where('status', PostStatus::PendingReview)->count(),
            'Corrections required' => (clone $query)->where('status', PostStatus::Rejected)->count(),
            'My published posts' => (clone $query)->where('status', PostStatus::Published)->count(),
            'My total views' => (int) (clone $query)->sum('views_count'),
        ];
    }

    protected function getStats(): array
    {
        return collect($this->metrics())->map(fn (int $value, string $label): Stat => Stat::make($label, number_format($value)))->values()->all();
    }
}
