<?php

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
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
        return [
            'Published posts' => Post::query()->where('status', PostStatus::Published)->count(),
            'Draft posts' => Post::query()->where('status', PostStatus::Draft)->count(),
            'Pending review' => Post::query()->where('status', PostStatus::PendingReview)->count(),
            'Users' => User::query()->count(),
            'Content staff' => User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', ['editor', 'reporter', 'author']))->count(),
            'Categories' => Category::query()->count(),
            'Tags' => Tag::query()->count(),
            'Article views' => (int) Post::query()->sum('views_count'),
        ];
    }

    protected function getStats(): array
    {
        return collect($this->metrics())->map(fn (int $value, string $label): Stat => Stat::make($label, number_format($value)))->values()->all();
    }
}
