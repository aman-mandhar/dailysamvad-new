<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Support\Authorization\ContentAccess;
use App\Support\PostSeoData;
use App\Support\PostTaxonomy;
use App\Support\PostWorkflow;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['primary_category_id'] = $this->record
            ->primaryCategory()
            ->value('categories.id');
        $data['robots'] = PostSeoData::robotsDirective($this->record->seo_data);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['seo_data'] = PostSeoData::mergeRobots(
            $this->record->seo_data,
            $this->data['robots'] ?? null,
        );

        $data = PostWorkflow::prepareForPersistence($data, $this->record);

        if (! ContentAccess::canAssignPostAuthor(auth()->user())) {
            $data['author_id'] = $this->record->author_id;
        }

        return $data;
    }

    protected function beforeSave(): void
    {
        $errors = PostTaxonomy::validate($this->data);

        $errors = [
            ...$errors,
            ...PostWorkflow::validate(auth()->user(), $this->record->status, $this->data),
        ];

        if ($errors !== []) {
            throw ValidationException::withMessages(
                collect($errors)->mapWithKeys(fn (string $message, string $field): array => ["data.{$field}" => $message])->all(),
            );
        }
    }

    protected function afterSave(): void
    {
        PostTaxonomy::syncPrimaryCategory($this->record, (int) $this->data['primary_category_id']);
    }
}
