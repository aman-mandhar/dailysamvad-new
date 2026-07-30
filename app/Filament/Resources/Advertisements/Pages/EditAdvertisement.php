<?php

namespace App\Filament\Resources\Advertisements\Pages;

use App\Filament\Resources\Advertisements\AdvertisementResource;
use Filament\Resources\Pages\EditRecord;

class EditAdvertisement extends EditRecord
{
    protected static string $resource = AdvertisementResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
