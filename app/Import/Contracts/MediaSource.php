<?php

namespace App\Import\Contracts;

interface MediaSource
{
    public function exists(string $path): bool;

    /** @return resource|null */
    public function readStream(string $path): mixed;

    public function size(string $path): int;

    public function mimeType(string $path): ?string;
}
