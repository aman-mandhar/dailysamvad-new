<?php

namespace App\Import\Contracts;

use App\Import\DTOs\ImportStatistics;

interface Logger
{
    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function success(string $message, array $context = []): void;

    public function summary(ImportStatistics $statistics): void;
}
