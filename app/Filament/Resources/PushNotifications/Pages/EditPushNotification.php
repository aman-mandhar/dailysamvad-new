<?php

namespace App\Filament\Resources\PushNotifications\Pages;

use App\Filament\Resources\PushNotifications\PushNotificationResource;
use Filament\Resources\Pages\EditRecord;

class EditPushNotification extends EditRecord
{
    protected static string $resource = PushNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [PushNotificationResource::sendAction()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['title'] = trim((string) $data['title']);
        $data['body'] = trim((string) $data['body']);

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Push notification draft saved';
    }
}
