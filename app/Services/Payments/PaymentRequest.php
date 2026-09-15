<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Models\JobPayment;

/**
 * @param  array<string, mixed>  $meta
 */
readonly class PaymentRequest
{
    public function __construct(
        public JobPayment $payment,
        public PaymentMethod $method,
        public string $amount,
        public ?string $reference = null,
        public array $meta = [],
    ) {}
}
