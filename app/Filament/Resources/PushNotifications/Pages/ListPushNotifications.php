<?php

namespace App\Filament\Resources\PushNotifications\Pages;

use App\Filament\Resources\PushNotifications\PushNotificationResource;
use App\Models\PushSubscription;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPushNotifications extends ListRecords
{
    protected static string $resource = PushNotificationResource::class;

    public function getSubheading(): ?string
    {
        return 'All Active Subscribers · '.number_format(PushSubscription::query()->active()->count()).' currently active';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Create Draft')];
    }
}
