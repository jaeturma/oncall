<?php

namespace App\Services\Notifications\Fcm;

use App\Enums\PushFailureType;

final class FcmSendResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly ?string $providerMessageId,
        public readonly ?PushFailureType $failureType,
        public readonly ?string $errorCode,
    ) {}

    public static function success(string $providerMessageId): self
    {
        return new self(true, $providerMessageId, null, null);
    }

    public static function failure(PushFailureType $type, string $errorCode): self
    {
        return new self(false, null, $type, $errorCode);
    }
}
