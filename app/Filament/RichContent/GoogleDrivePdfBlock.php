<?php

namespace App\Filament\RichContent;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;

class GoogleDrivePdfBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'google-drive-pdf';
    }

    public static function getLabel(): string
    {
        return 'Google Drive PDF';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('url')->label('Google Drive PDF link')->url()->required(),
            TextInput::make('title')->default('PDF document')->maxLength(255),
        ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        return 'PDF: '.($config['title'] ?? 'Document');
    }

    public static function toPreviewHtml(array $config): ?string
    {
        return static::toHtml($config, []);
    }

    public static function toHtml(array $config, array $data): ?string
    {
        $url = (string) ($config['url'] ?? '');
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (! in_array($host, ['drive.google.com', 'docs.google.com'], true)) {
            return null;
        }
        preg_match('#/(?:file/d/|open\?id=)([A-Za-z0-9_-]+)#', $url, $matches);
        $id = $matches[1] ?? null;
        if (! $id) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $id = $query['id'] ?? null;
        }
        if (! is_string($id) || preg_match('/^[A-Za-z0-9_-]+$/', $id) !== 1) {
            return null;
        }

        $preview = 'https://drive.google.com/file/d/'.rawurlencode($id).'/preview';

        return '<figure class="ds-article-embed ds-article-embed--pdf"><iframe src="'.$preview.'" title="'.e($config['title'] ?? 'PDF document').'" loading="lazy"></iframe><figcaption><a href="https://drive.google.com/file/d/'.rawurlencode($id).'/view" target="_blank">Open PDF in Google Drive</a></figcaption></figure>';
    }
}
