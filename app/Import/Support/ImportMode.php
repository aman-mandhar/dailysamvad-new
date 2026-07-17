<?php

namespace App\Import\Support;

enum ImportMode: string
{
    case DryRun = 'dry-run';
    case Live = 'live';
}
