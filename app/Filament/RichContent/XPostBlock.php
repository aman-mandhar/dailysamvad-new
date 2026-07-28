<?php

namespace App\Filament\RichContent;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;

class XPostBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'x-post';
    }

    public static function getLabel(): string
    {
        return 'X post';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([TextInput::make('url')->label('X post URL')->url()->required()]);
    }

    public static function getPreviewLabel(array $config): string
    {
        return 'X post';
    }

    public static function toPreviewHtml(array $config): ?string
    {
        return static::toHtml($config, []);
    }

    public static function toHtml(array $config, array $data): ?string
    {
        $url = (string) ($config['url'] ?? '');
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (! in_array($host, ['x.com', 'www.x.com', 'twitter.com', 'www.twitter.com'], true)) {
            return null;
        }
        preg_match('#/status/(\d+)#', (string) parse_url($url, PHP_URL_PATH), $matches);
        $id = $matches[1] ?? null;
        if (! $id) {
            return null;
        }

        return '<figure class="ds-article-embed ds-article-embed--x"><iframe src="https://platform.twitter.com/embed/Tweet.html?id='.e($id).'" title="X post" loading="lazy"></iframe><figcaption><a href="'.e($url).'" target="_blank">View post on X</a></figcaption></figure>';
    }
}
