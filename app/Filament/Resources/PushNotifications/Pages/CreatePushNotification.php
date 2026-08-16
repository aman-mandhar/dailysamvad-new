<?php

namespace App\Filament\Resources\PushNotifications\Pages;

use App\Enums\PushNotificationStatus;
use App\Filament\Resources\PushNotifications\PushNotificationResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePushNotification extends CreateRecord
{
    protected static string $resource = PushNotificationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['title'] = trim((string) $data['title']);
        $data['body'] = trim((string) $data['body']);
        $data['created_by'] = auth()->id();
        $data['status'] = PushNotificationStatus::Draft->value;

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Push notification draft saved';
    }
}
