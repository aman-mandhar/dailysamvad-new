<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AdministrativeOverviewWidget;
use App\Filament\Widgets\EditorialOverviewWidget;
use App\Filament\Widgets\OwnPostOverviewWidget;
use App\Filament\Widgets\StaffWelcomeWidget;
use App\Filament\Pages\SuperAdminDashboard;
use App\Filament\Pages\AdminDashboard;
use App\Filament\Pages\EditorDashboard;
use App\Filament\Pages\ReviewerDashboard;
use App\Filament\Pages\ReporterDashboard;
use App\Filament\Pages\SeoDashboard;
use App\Filament\Pages\MediaDashboard;
use App\Filament\Pages\AnalyticsDashboard;
use App\Filament\Pages\ContributorDashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->spa(hasPrefetching: true)
            ->font('Instrument Sans')
            ->maxContentWidth(Width::ScreenTwoExtraLarge)
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->navigationGroups([
                'Workspaces', 'Content', 'Taxonomy', 'Media', 'Users', 'System',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                SuperAdminDashboard::class,
                AdminDashboard::class,
                EditorDashboard::class,
                ReviewerDashboard::class,
                ReporterDashboard::class,
                SeoDashboard::class,
                MediaDashboard::class,
                AnalyticsDashboard::class,
                ContributorDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                StaffWelcomeWidget::class,
                AdministrativeOverviewWidget::class,
                EditorialOverviewWidget::class,
                OwnPostOverviewWidget::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
