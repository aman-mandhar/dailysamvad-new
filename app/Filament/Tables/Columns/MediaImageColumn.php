<?php

namespace App\Filament\Tables\Columns;

use App\Support\MediaUrlResolver;
use Filament\Tables\Columns\ImageColumn;

class MediaImageColumn extends ImageColumn
{
    public function getImageUrl(?string $state = null): ?string
    {
        return app(MediaUrlResolver::class)->resolveExisting($state, $this->getDiskName());
    }
}
