<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Services\MediaUploadService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $upload = $data['upload'] ?? null;
        if (is_array($upload)) {
            $upload = reset($upload);
        }
        abort_unless($upload instanceof UploadedFile, 422, 'A valid image upload is required.');

        return app(MediaUploadService::class)->store($upload, auth()->id(), $data);
    }
}
