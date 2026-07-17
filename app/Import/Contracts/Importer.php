<?php

namespace App\Import\Contracts;

use App\Import\DTOs\ImportContext;
use App\Import\DTOs\ImportResult;

interface Importer
{
    public function key(): string;

    public function import(ImportContext $context): ImportResult;
}
