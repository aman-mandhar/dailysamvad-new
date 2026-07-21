<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Support\Authorization\ContentAccess;
use App\Support\PostSeoData;
use App\Support\PostTaxonomy;
use App\Support\PostWorkflow;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function beforeCreate(): void
    {
        $this->validateTaxonomy();
        $this->validateWorkflow(PostStatus::Draft);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['seo_data'] = PostSeoData::mergeRobots(null, $this->data['robots'] ?? null);

        $data = PostWorkflow::prepareForPersistence($data);

        if (! ContentAccess::canAssignPostAuthor(auth()->user())) {
            $data['author_id'] = auth()->id();
        }

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

    private function validateWorkflow(PostStatus $from): void
    {
        $errors = PostWorkflow::validate(auth()->user(), $from, $this->data);

        if ($errors !== []) {
            throw ValidationException::withMessages(
                collect($errors)->mapWithKeys(fn (string $message, string $field): array => ["data.{$field}" => $message])->all(),
            );
        }
    }
}
