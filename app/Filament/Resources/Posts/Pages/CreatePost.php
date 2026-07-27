<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\PostWorkflowEvent;
use App\Support\Authorization\ContentAccess;
use App\Support\PostSeoData;
use App\Support\PostTaxonomy;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function getRedirectUrl(): string
    {
        return PostResource::getUrl('index');
    }

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

        $canPublish = auth()->user()?->can('publish posts') ?? false;
        $status = $canPublish && ($data['status'] ?? null) === PostStatus::Published->value
            ? PostStatus::Published
            : PostStatus::Draft;

        $data['status'] = $status->value;
        $data['published_at'] = $status === PostStatus::Published ? now() : null;
        unset($data['scheduled_at']);

        if (! ContentAccess::canAssignPostAuthor(auth()->user())) {
            $data['author_id'] = auth()->id();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        PostTaxonomy::syncPrimaryCategory($this->record, (int) $this->data['primary_category_id']);

        if ($this->record->status === PostStatus::Published) {
            $this->record->forceFill(['published_by' => auth()->id()])->save();

            PostWorkflowEvent::query()->create([
                'post_id' => $this->record->getKey(),
                'actor_id' => auth()->id(),
                'event' => 'published',
                'from_status' => null,
                'to_status' => PostStatus::Published->value,
                'metadata' => ['source' => 'direct_creation'],
            ]);
        }
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
