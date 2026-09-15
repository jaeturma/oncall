<?php

namespace App\Services\Payments;

/**
 * A payment gateway turns a customer-reported (or, for a future real
 * integration, provider-confirmed) payment into an authoritative result.
 * Laravel — never Flutter, never a redirect — decides whether a payment or
 * refund succeeded; a gateway implementation only reports what it observed.
 */
interface PaymentGatewayInterface
{
    public function identifier(): string;

    public function charge(PaymentRequest $request): PaymentResult;

    public function refund(RefundRequest $request): RefundResult;
}
