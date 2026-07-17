<?php

namespace App\Import\Services;

use App\Import\Contracts\Importer;
use App\Import\Exceptions\ImporterNotFoundException;

class ImportRegistry
{
    /** @var array<string, Importer> */
    private array $importers = [];

    public function register(Importer $importer): void
    {
        $this->importers[$importer->key()] = $importer;
    }

    public function get(string $key): Importer
    {
        return $this->importers[$key] ?? throw new ImporterNotFoundException("Importer [{$key}] is not registered.");
    }

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys($this->importers);
    }
}
