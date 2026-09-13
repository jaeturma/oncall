<?php

namespace App\Services;

/** The outcome of {@see OtpService::request()}. */
final readonly class OtpRequestResult
{
    public function __construct(
        public bool $sent,
        public string $mobile,
        public int $expiresInSeconds,
        public int $resendAvailableInSeconds,
        public ?string $plainCode = null,
        public ?string $smsErrorMessage = null,
    ) {}
}
