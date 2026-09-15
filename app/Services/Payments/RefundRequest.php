<?php

namespace App\Services\Payments;

use App\Models\JobPayment;

readonly class RefundRequest
{
    public function __construct(
        public JobPayment $payment,
        public string $amount,
        public ?string $reference = null,
    ) {}
}
