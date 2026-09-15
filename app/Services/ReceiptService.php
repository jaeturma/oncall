<?php

namespace App\Services;

use App\Models\JobPayment;

/**
 * Builds the sanitized, customer-facing receipt payload for a job payment.
 * Read-only — reuses the relations JobPayment/Job/ServiceRequest already
 * expose, no new persistence.
 */
class ReceiptService
{
    /**
     * @return array<string, mixed>
     */
    public function forPayment(JobPayment $payment): array
    {
        $payment->loadMissing(['job.serviceRequest.service', 'job.serviceFinder', 'provider']);
        $job = $payment->job;

        return [
            'receipt_number' => $payment->receipt_number,
            'status' => $payment->status->value,
            'purpose' => $payment->purpose->value,
            'gross_amount' => (string) $payment->gross_amount,
            'platform_fee' => (string) $payment->platform_fee,
            'net_amount' => (string) $payment->net_amount,
            'refunded_amount' => (string) $payment->refunded_amount,
            'refundable_amount' => $payment->refundableAmount(),
            'payment_method' => $payment->payment_method,
            'payment_reference' => $payment->payment_reference,
            'confirmed_at' => $payment->confirmed_at?->toIso8601String(),
            'released_at' => $payment->released_at?->toIso8601String(),
            'service' => $job->serviceRequest?->service?->name,
            'customer_name' => $job->serviceFinder?->name,
            'provider_name' => $payment->provider?->name,
            'job_id' => $job->id,
        ];
    }
}
