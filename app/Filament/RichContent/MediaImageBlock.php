<?php

namespace App\Filament\RichContent;

use App\Models\Media;
use App\Support\MediaUrlResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class MediaImageBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'media-image';
    }

    public static function getLabel(): string
    {
        return 'Image from media library';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            Select::make('media_id')
                ->label('Media image')
                ->options(fn (): array => Media::query()
                    ->where('mime_type', 'like', 'image/%')
                    ->whereNull('missing_at')
                    ->latest('id')
                    ->limit(250)
                    ->get()
                    ->mapWithKeys(fn (Media $media): array => [
                        $media->id => $media->original_filename ?: basename($media->path),
                    ])->all())
                ->searchable()
                ->required(),
            TextInput::make('alt')->label('Alternative text')->maxLength(255),
            TextInput::make('caption')->maxLength(500),
        ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        return 'Media image #'.($config['media_id'] ?? '');
    }

    public static function toPreviewHtml(array $config): ?string
    {
        return static::toHtml($config, []);
    }

    public static function toHtml(array $config, array $data): ?string
    {
        $media = Media::query()->find((int) ($config['media_id'] ?? 0));
        if (! $media || ! str_starts_with((string) $media->mime_type, 'image/') || $media->missing_at) {
            return null;
        }

        $url = app(MediaUrlResolver::class)->resolve($media->path, $media->disk);
        if (! $url) {
            return null;
        }

        $alt = e(filled($config['alt'] ?? null) ? $config['alt'] : ($media->alt_text ?: $media->original_filename));
        $caption = filled($config['caption'] ?? null) ? (string) $config['caption'] : (string) $media->caption;
        $image = '<img src="'.e($url).'" alt="'.$alt.'" loading="lazy">';

        return $caption !== ''
            ? '<figure class="ds-article-embed ds-article-embed--image">'.$image.'<figcaption>'.e($caption).'</figcaption></figure>'
            : '<figure class="ds-article-embed ds-article-embed--image">'.$image.'</figure>';
    }
}
