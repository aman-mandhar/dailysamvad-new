<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Support\UserAdministration;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function beforeCreate(): void
    {
        $errors = UserAdministration::validateNewUserRoles(
            auth()->user(),
            $this->data['roles'] ?? [],
        );

        if ($errors !== []) {
            throw ValidationException::withMessages(
                collect($errors)->mapWithKeys(fn (string $message, string $field): array => ["data.{$field}" => $message])->all(),
            );
        }
    }
}
