<?php

namespace App\Import\Contracts;

use App\Import\DTOs\ImportContext;
use App\Import\DTOs\VerificationResult;

interface Verifier
{
    public function verify(ImportContext $context): VerificationResult;
}
