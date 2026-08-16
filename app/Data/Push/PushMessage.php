<?php

namespace App\Data\Push;

use InvalidArgumentException;

final readonly class PushMessage
{
    /** @param array<string, scalar|null> $data */
    public function __construct(
        public string $title,
        public string $body,
        public ?string $image = null,
        public ?string $url = null,
        public ?string $icon = null,
        public array $data = [],
    ) {
        if (blank(trim($title)) || mb_strlen($title) > 200) {
            throw new InvalidArgumentException('Push title must contain 1 to 200 characters.');
        }
        if (blank(trim($body)) || mb_strlen($body) > 1000) {
            throw new InvalidArgumentException('Push body must contain 1 to 1000 characters.');
        }
        foreach (['image' => $image, 'url' => $url, 'icon' => $icon] as $field => $value) {
            if ($value !== null && filter_var($value, FILTER_VALIDATE_URL) === false) {
                throw new InvalidArgumentException("Push {$field} must be an absolute URL.");
            }
        }
        if (count($data) > 50 || strlen((string) json_encode($data)) > 3000) {
            throw new InvalidArgumentException('Push data payload is too large.');
        }
        foreach ($data as $key => $value) {
            if (! is_string($key) || $key === '' || (! is_scalar($value) && $value !== null)) {
                throw new InvalidArgumentException('Push data must contain string keys and scalar values.');
            }
        }
    }

    /** @return array{title:string,body:string,image:?string,url:?string,icon:?string,data:array<string, scalar|null>} */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** @param array{title:string,body:string,image?:?string,url?:?string,icon?:?string,data?:array<string, scalar|null>} $payload */
    public static function fromArray(array $payload): self
    {
        return new self($payload['title'], $payload['body'], $payload['image'] ?? null, $payload['url'] ?? null, $payload['icon'] ?? null, $payload['data'] ?? []);
    }
}
