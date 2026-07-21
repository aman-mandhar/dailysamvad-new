<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Posts\PostResource;
use Filament\Widgets\Widget;

class StaffWelcomeWidget extends Widget
{
    protected string $view = 'filament.widgets.staff-welcome';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->can('view admin dashboard') ?? false;
    }

    /** @return array{heading: string, description: string, role: string, createUrl: ?string} */
    public function context(): array
    {
        $user = auth()->user();
        $role = $user?->getRoleNames()->first() ?? 'staff';
        [$heading, $description] = match ($role) {
            'super-admin' => ['System overview', 'Monitor publishing, users, taxonomy, media, and platform activity.'],
            'admin' => ['Administration and publishing overview', 'Manage the editorial and administrative areas assigned to you.'],
            'editor' => ['Editorial review overview', 'Review the current publishing pipeline and editorial workload.'],
            'reporter' => ['Your reporting workspace', 'Create stories and manage only your reporting work.'],
            'author' => ['Your article workspace', 'Create and manage only your own articles.'],
            default => ['Staff dashboard', 'Use the areas assigned to your account.'],
        };

        return [
            'heading' => $heading,
            'description' => $description,
            'role' => $role,
            'createUrl' => $user?->can('create posts') ? PostResource::getUrl('create') : null,
        ];
    }

    protected function getViewData(): array
    {
        return $this->context();
    }
}
