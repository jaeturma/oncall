<?php

namespace App\Services;

use App\Enums\JobPaymentStatus;
use App\Enums\RefundStatus;
use App\Models\JobPayment;
use App\Models\PaymentSetting;

/**
 * Single source of truth for whether a job payment can still be refunded,
 * shared by the customer-facing request flow and the back-office approval
 * flow (defense in depth: both call through here rather than duplicating
 * the rules).
 */
class RefundEligibilityService
{
    /**
     * @param  int|null  $excludingRefundId  Pass the refund's own id when re-checking
     *                                       eligibility at approval time, so it does not
     *                                       count against its own request-limit slot.
     * @return array{eligible: bool, max_refundable: string, reason: ?string}
     */
    public function eligible(JobPayment $payment, string $amount, ?int $excludingRefundId = null): array
    {
        $maxRefundable = $payment->refundableAmount();

        if (! in_array($payment->status, [JobPaymentStatus::Paid, JobPaymentStatus::Released], true)) {
            return $this->result(false, $maxRefundable, 'This payment is not in a refundable state.');
        }

        if ($payment->job->dispute?->isOpen()) {
            return $this->result(false, $maxRefundable, 'This job has an open dispute; refunds are handled through dispute resolution.');
        }

        if (bccomp($amount, '0', 2) !== 1) {
            return $this->result(false, $maxRefundable, 'The refund amount must be greater than zero.');
        }

        if (bccomp($amount, $maxRefundable, 2) === 1) {
            return $this->result(false, $maxRefundable, 'The refund amount exceeds what remains refundable on this payment.');
        }

        $settings = PaymentSetting::current();
        $confirmedAt = $payment->confirmed_at;

        if ($confirmedAt !== null && $confirmedAt->lt(now()->subDays($settings->refund_window_days))) {
            return $this->result(false, $maxRefundable, 'This payment is outside the refund window.');
        }

        $existingRequests = $payment->refunds()
            ->whereNotIn('status', [RefundStatus::Rejected, RefundStatus::Failed])
            ->when($excludingRefundId !== null, fn ($query) => $query->whereKeyNot($excludingRefundId))
            ->count();

        if ($existingRequests >= $settings->max_refund_requests_per_payment) {
            return $this->result(false, $maxRefundable, 'The maximum number of refund requests for this payment has been reached.');
        }

        return $this->result(true, $maxRefundable, null);
    }

    /**
     * @return array{eligible: bool, max_refundable: string, reason: ?string}
     */
    private function result(bool $eligible, string $maxRefundable, ?string $reason): array
    {
        return ['eligible' => $eligible, 'max_refundable' => $maxRefundable, 'reason' => $reason];
    }
}
