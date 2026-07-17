<?php

namespace App\Filament\Pages;

use App\Import\Services\ImportReportStore;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ImportDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Import Dashboard';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.import-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage settings') ?? false;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $reports = app(ImportReportStore::class);
        $latest = $reports->read('latest');
        $verification = $reports->read('verification-latest');
        $history = $reports->read('history')['runs'] ?? [];
        $events = collect($reports->read('events')['events'] ?? [])
            ->whereIn('level', ['error', 'warning'])->take(10)->values()->all();

        return compact('latest', 'verification', 'history', 'events');
    }
}
