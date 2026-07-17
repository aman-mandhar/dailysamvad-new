<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Support\PostSeoData;
use App\Support\PostTaxonomy;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function beforeCreate(): void
    {
        $this->validateTaxonomy();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['seo_data'] = PostSeoData::mergeRobots(null, $this->data['robots'] ?? null);

        return $data;
    }

    protected function afterCreate(): void
    {
        PostTaxonomy::syncPrimaryCategory($this->record, (int) $this->data['primary_category_id']);
    }

    private function validateTaxonomy(): void
    {
        $errors = PostTaxonomy::validate($this->data);

        if ($errors !== []) {
            throw ValidationException::withMessages(
                collect($errors)->mapWithKeys(fn (string $message, string $field): array => ["data.{$field}" => $message])->all(),
            );
        }
    }
}
