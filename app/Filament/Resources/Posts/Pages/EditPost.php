<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\User;
use App\Services\EditorialWorkflowService;
use App\Support\Authorization\ContentAccess;
use App\Support\PostSeoData;
use App\Support\PostTaxonomy;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        $run = function (string $method, array $arguments = []): void {
            app(EditorialWorkflowService::class)->{$method}($this->record, auth()->user(), ...$arguments);
            $this->record->refresh();
            Notification::make()->title('Workflow updated')->success()->send();
        };

        return [
            Action::make('submit_for_review')->visible(fn (): bool => $this->hasStatus(PostStatus::Draft, PostStatus::ChangesRequested) && auth()->user()->can('submitForReview', $this->record))->action(fn () => $run('submitForReview')),
            Action::make('assign_reviewer')->visible(fn (): bool => $this->hasStatus(PostStatus::PendingReview) && auth()->user()->can('assignReviewer', $this->record))->schema([
                Select::make('reviewer_id')->options(fn () => User::query()->where('is_active', true)->permission('review posts')->orderBy('name')->pluck('name', 'id'))->required()->searchable(),
            ])->action(fn (array $data) => $run('assignReviewer', [User::findOrFail($data['reviewer_id'])])),
            Action::make('start_review')->visible(fn (): bool => $this->hasStatus(PostStatus::PendingReview) && auth()->user()->can('startReview', $this->record))->action(fn () => $run('startReview')),
            Action::make('request_corrections')->visible(fn (): bool => $this->hasStatus(PostStatus::PendingReview, PostStatus::Approved) && auth()->user()->can('requestCorrections', $this->record))->schema([
                Textarea::make('notes')->required(),
            ])->action(fn (array $data) => $run('requestCorrections', [$data['notes']])),
            Action::make('approve')->visible(fn (): bool => $this->hasStatus(PostStatus::PendingReview) && auth()->user()->can('approve', $this->record))->schema([
                Textarea::make('notes'),
            ])->action(fn (array $data) => $run('approve', [$data['notes'] ?? null])),
            Action::make('reject')->visible(fn (): bool => $this->hasStatus(PostStatus::PendingReview) && auth()->user()->can('reject', $this->record))->schema([
                Textarea::make('reason')->required(),
            ])->action(fn (array $data) => $run('reject', [$data['reason']])),
            Action::make('schedule')->visible(fn (): bool => $this->hasStatus(PostStatus::Approved, PostStatus::Scheduled) && auth()->user()->can('schedule', $this->record))->schema([
                DateTimePicker::make('scheduled_at')->required()->minDate(now()),
            ])->action(fn (array $data) => $run('schedule', [$data['scheduled_at']])),
            Action::make('cancel_schedule')->visible(fn (): bool => $this->hasStatus(PostStatus::Scheduled) && auth()->user()->can('schedule', $this->record))->action(fn () => $run('cancelSchedule')),
            Action::make('publish')->visible(fn (): bool => $this->hasStatus(PostStatus::Approved, PostStatus::Scheduled) && auth()->user()->can('publish', $this->record))->requiresConfirmation()->action(fn () => $run('publish')),
            Action::make('archive')->visible(fn (): bool => $this->hasStatus(PostStatus::Published) && auth()->user()->can('archive', $this->record))->requiresConfirmation()->action(fn () => $run('archive')),
            Action::make('reopen')->visible(fn (): bool => $this->hasStatus(PostStatus::Rejected, PostStatus::Archived) && auth()->user()->can('restoreWorkflow', $this->record))->action(fn () => $run('reopen')),
        ];
    }

    private function hasStatus(PostStatus ...$statuses): bool
    {
        return in_array($this->record->status, $statuses, true);
    }

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

        unset($data['status'], $data['published_at'], $data['scheduled_at']);

        if (! ContentAccess::canAssignPostAuthor(auth()->user())) {
            $data['author_id'] = $this->record->author_id;
        }

        return $data;
    }

    protected function beforeSave(): void
    {
        $errors = PostTaxonomy::validate($this->data);

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
