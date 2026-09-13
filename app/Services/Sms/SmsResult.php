<?php

namespace App\Services\Sms;

use App\Enums\SmsDeliveryStatus;

/**
 * The outcome of one send/test attempt. `errorMessage` must already be
 * sanitized by the provider that produced it — never the raw provider
 * response body — since it may be shown to a web admin.
 */
final readonly class SmsResult
{
    public function __construct(
        public bool $successful,
        public SmsDeliveryStatus $status,
        public ?string $providerMessageId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    public static function success(SmsDeliveryStatus $status = SmsDeliveryStatus::Sent, ?string $providerMessageId = null): self
    {
        return new self(true, $status, $providerMessageId);
    }

    public static function failure(string $errorCode, string $errorMessage, SmsDeliveryStatus $status = SmsDeliveryStatus::Failed): self
    {
        return new self(false, $status, null, $errorCode, $errorMessage);
    }
}
