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
        public ?int $advertisementId = null,
        public ?string $advertisementUuid = null,
        public ?string $videoUrl = null,
        public ?string $posterUrl = null,
        public bool $autoplay = false,
        public bool $muted = true,
        public bool $loop = false,
        public bool $controls = true,
        public ?string $clickUrl = null,
        public ?string $impressionUrl = null,
        public ?string $editUrl = null,
        public bool $canEdit = false,
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

    /** @return array<string, bool|int|string|null> */
    public function toCacheArray(): array
    {
        return get_object_vars($this);
    }

    /** @param array<string, mixed> $data */
    public static function fromCacheArray(array $data): self
    {
        return new self(
            slot: (string) $data['slot'],
            enabled: (bool) $data['enabled'],
            type: (string) $data['type'],
            label: (string) $data['label'],
            html: isset($data['html']) ? (string) $data['html'] : null,
            imageUrl: isset($data['imageUrl']) ? (string) $data['imageUrl'] : null,
            destinationUrl: isset($data['destinationUrl']) ? (string) $data['destinationUrl'] : null,
            altText: (string) $data['altText'],
            width: (int) $data['width'],
            height: (int) $data['height'],
            openInNewTab: (bool) $data['openInNewTab'],
            rel: (string) $data['rel'],
            advertisementId: isset($data['advertisementId']) ? (int) $data['advertisementId'] : null,
            advertisementUuid: isset($data['advertisementUuid']) ? (string) $data['advertisementUuid'] : null,
            videoUrl: isset($data['videoUrl']) ? (string) $data['videoUrl'] : null,
            posterUrl: isset($data['posterUrl']) ? (string) $data['posterUrl'] : null,
            autoplay: (bool) ($data['autoplay'] ?? false),
            muted: (bool) ($data['muted'] ?? true),
            loop: (bool) ($data['loop'] ?? false),
            controls: (bool) ($data['controls'] ?? true),
            clickUrl: isset($data['clickUrl']) ? (string) $data['clickUrl'] : null,
            impressionUrl: isset($data['impressionUrl']) ? (string) $data['impressionUrl'] : null,
            editUrl: isset($data['editUrl']) ? (string) $data['editUrl'] : null,
            canEdit: (bool) ($data['canEdit'] ?? false),
        );
    }
}
