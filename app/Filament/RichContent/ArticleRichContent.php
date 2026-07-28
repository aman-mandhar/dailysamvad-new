<?php

namespace App\Filament\RichContent;

class ArticleRichContent
{
    /** @return array<string, array<class-string>> */
    public static function blocks(): array
    {
        return [
            'Media and embeds' => [
                MediaImageBlock::class,
                YouTubeBlock::class,
                GoogleDrivePdfBlock::class,
                XPostBlock::class,
            ],
        ];
    }
}
