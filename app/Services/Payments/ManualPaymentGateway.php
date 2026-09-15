<?php

namespace App\Services\Payments;

/**
 * The only gateway registered today. Oncall does not process payments —
 * money changes hands off-platform (cash, GCash, Maya, bank transfer) and the
 * customer/back-office merely report what happened. This gateway performs no
 * external call; it exists so the rest of the payment pipeline is written
 * against {@see PaymentGatewayInterface} and a real aggregator (e.g. PayMongo)
 * can be added later without touching JobPaymentService/RefundService.
 */
class ManualPaymentGateway implements PaymentGatewayInterface
{
    public function identifier(): string
    {
        return 'MANUAL';
    }

    public function charge(PaymentRequest $request): PaymentResult
    {
        return new PaymentResult(
            successful: true,
            gateway: $this->identifier(),
            gatewayReference: $request->reference,
        );
    }

    public function refund(RefundRequest $request): RefundResult
    {
        return new RefundResult(
            successful: true,
            gateway: $this->identifier(),
            gatewayReference: $request->reference,
        );
    }
}
