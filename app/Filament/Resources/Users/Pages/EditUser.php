<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Support\UserAdministration;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $errors = UserAdministration::validateChanges(
            auth()->user(),
            $this->record,
            (bool) ($this->data['is_active'] ?? $this->record->is_active),
            $this->data['roles'] ?? $this->record->roles()->pluck('roles.id')->all(),
        );

        if ($errors !== []) {
            throw ValidationException::withMessages(
                collect($errors)->mapWithKeys(fn (string $message, string $field): array => ["data.{$field}" => $message])->all(),
            );
        }
    }
}
