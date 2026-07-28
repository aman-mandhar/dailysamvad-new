<?php

namespace App\Filament\RichContent;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;

class YouTubeBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'youtube-video';
    }

    public static function getLabel(): string
    {
        return 'YouTube video';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('url')->label('YouTube URL')->url()->required(),
            TextInput::make('title')->default('YouTube video')->maxLength(255),
        ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        return 'YouTube: '.($config['title'] ?? 'Video');
    }

    public static function toPreviewHtml(array $config): ?string
    {
        return static::toHtml($config, []);
    }

    public static function toHtml(array $config, array $data): ?string
    {
        $url = (string) ($config['url'] ?? '');
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $id = $host === 'youtu.be' ? $path : null;
        if (str_ends_with($host, 'youtube.com')) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $id = $query['v'] ?? (str_starts_with($path, 'embed/') ? substr($path, 6) : (str_starts_with($path, 'shorts/') ? substr($path, 7) : null));
        }
        if (! is_string($id) || preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id) !== 1) {
            return null;
        }

        return '<figure class="ds-article-embed ds-article-embed--video"><iframe src="https://www.youtube-nocookie.com/embed/'.e($id).'" title="'.e($config['title'] ?? 'YouTube video').'" loading="lazy" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></figure>';
    }
}
