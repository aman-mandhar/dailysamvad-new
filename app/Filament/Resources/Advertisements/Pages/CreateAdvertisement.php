<?php

namespace App\Filament\Resources\Advertisements\Pages;

use App\Filament\Resources\Advertisements\AdvertisementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdvertisement extends CreateRecord
{
    protected static string $resource = AdvertisementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
