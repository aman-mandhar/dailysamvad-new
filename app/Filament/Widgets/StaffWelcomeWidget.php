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
        [$heading, $description] = match (true) {
            (bool) $user?->can('manage permissions') => ['System overview', 'Monitor authorization, publishing, taxonomy, media, and platform activity.'],
            (bool) $user?->can('manage users') => ['Administration overview', 'Manage the administrative and publishing areas assigned to you.'],
            (bool) $user?->can('review posts') && (bool) $user?->can('view all posts') => ['Editorial review overview', 'Review the publishing pipeline and editorial workload.'],
            (bool) $user?->can('review posts') => ['Review queue', 'Work only on posts assigned to your review scope.'],
            (bool) $user?->can('manage seo') || (bool) $user?->can('view seo') => ['SEO workspace', 'Audit and improve authorized editorial metadata.'],
            (bool) $user?->can('manage media') || (bool) $user?->can('view media') => ['Media workspace', 'Manage authorized media and usage data.'],
            (bool) $user?->can('view all analytics') || (bool) $user?->can('view own analytics') => ['Analytics workspace', 'Review verified publication and visit metrics.'],
            (bool) $user?->can('view own posts') => ['Your reporting workspace', 'Create stories and manage only your reporting work.'],
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
