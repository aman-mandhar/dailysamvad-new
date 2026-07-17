<?php

namespace App\Data;

final readonly class AdvertisementData
{
    public function __construct(
        public string $slot,
        public bool $enabled,
        public string $type,
        public string $label,
        public ?string $html,
        public ?string $imageUrl,
        public ?string $destinationUrl,
        public string $altText,
        public int $width,
        public int $height,
        public bool $openInNewTab,
        public string $rel,
    ) {}

    public static function fromConfig(string $slot, array $config, bool $showPlaceholders): self
    {
        $type = in_array($config['type'] ?? null, ['html', 'image', 'placeholder'], true) ? $config['type'] : 'invalid';
        $html = is_string($config['html'] ?? null) && trim($config['html']) !== '' ? $config['html'] : null;
        $image = is_string($config['image'] ?? null) && trim($config['image']) !== '' ? $config['image'] : null;
        $valid = match ($type) {
            'html' => $html !== null,
            'image' => $image !== null,
            'placeholder' => $showPlaceholders,
            default => false,
        };

        return new self(
            slot: $slot,
            enabled: (bool) ($config['enabled'] ?? false) && $valid,
            type: $type,
            label: (string) ($config['label'] ?? 'Advertisement'),
            html: $html,
            imageUrl: $image,
            destinationUrl: filled($config['url'] ?? null) ? (string) $config['url'] : null,
            altText: (string) ($config['alt'] ?? ''),
            width: max(1, (int) ($config['width'] ?? 300)),
            height: max(1, (int) ($config['height'] ?? 250)),
            openInNewTab: (bool) ($config['open_in_new_tab'] ?? true),
            rel: (string) ($config['rel'] ?? 'sponsored noopener noreferrer'),
        );
    }
}
