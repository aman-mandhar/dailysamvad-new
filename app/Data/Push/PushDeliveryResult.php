<?php

namespace App\Data\Push;

final readonly class PushDeliveryResult
{
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public ?int $httpStatus = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public bool $tokenInvalid = false,
        public bool $retryable = false,
    ) {}

    public static function success(string $messageId, int $status = 200): self
    {
        return new self(true, $messageId, $status);
    }

    public static function failure(string $code, string $message, ?int $status = null, bool $tokenInvalid = false, bool $retryable = false): self
    {
        return new self(false, null, $status, $code, $message, $tokenInvalid, $retryable);
    }
}
