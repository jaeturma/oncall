<?php

namespace App\Services\Payments;

readonly class PaymentResult
{
    public function __construct(
        public bool $successful,
        public string $gateway,
        public ?string $gatewayReference = null,
        public ?string $failureReason = null,
    ) {}
}
