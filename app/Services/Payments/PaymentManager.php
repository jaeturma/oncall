<?php

namespace App\Services\Payments;

use App\Models\PaymentSetting;
use RuntimeException;

/**
 * Resolves the active {@see PaymentGatewayInterface} from admin settings.
 * Only "MANUAL" is registered today; a future real gateway registers itself
 * here without any change to the services that consume it.
 */
class PaymentManager
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    public function __construct(ManualPaymentGateway $manual)
    {
        $this->register($manual);
    }

    public function register(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$gateway->identifier()] = $gateway;
    }

    public function active(): PaymentGatewayInterface
    {
        $identifier = PaymentSetting::current()->active_gateway;

        return $this->gateways[$identifier]
            ?? throw new RuntimeException("No payment gateway is registered for \"{$identifier}\".");
    }
}
