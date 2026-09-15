<?php

namespace App\Services;

use App\Enums\CommissionType;
use App\Enums\JobPaymentStatus;
use App\Models\Job;
use App\Models\PaymentSetting;

/**
 * Computes the platform-fee split for a completed job. An account type's own
 * platform_commission_percent always wins when set; otherwise the platform-wide
 * default comes from {@see PaymentSetting} (admin-configurable Fixed/Percentage,
 * mirroring how sponsor commissions already work on AccountType).
 */
class FeeCalculationService
{
    /**
     * @return array<string, mixed>
     */
    public function split(Job $job): array
    {
        $gross = (string) $job->agreed_price;
        $fee = $this->platformFee($job, $gross);

        return [
            'provider_id' => $job->provider_id,
            'gross_amount' => $gross,
            'platform_fee' => $fee,
            'net_amount' => bcsub($gross, $fee, 2),
            'status' => JobPaymentStatus::Pending,
        ];
    }

    private function platformFee(Job $job, string $gross): string
    {
        $override = $job->provider->accountType?->platform_commission_percent;

        if ($override !== null) {
            return bcmul($gross, bcdiv((string) $override, '100', 6), 2);
        }

        $settings = PaymentSetting::current();

        return match ($settings->platform_fee_type) {
            CommissionType::Fixed => number_format((float) $settings->platform_fee_value, 2, '.', ''),
            CommissionType::Percentage => bcmul($gross, bcdiv((string) $settings->platform_fee_value, '100', 6), 2),
            CommissionType::None => '0.00',
        };
    }
}
