<?php

namespace App\Services;

use App\Enums\JobPaymentStatus;
use App\Enums\PaymentAttemptStatus;
use App\Enums\ReconciliationCategory;
use App\Enums\ReconciliationStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\AuditLog;
use App\Models\JobPayment;
use App\Models\PaymentAttempt;
use App\Models\ReconciliationFlag;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;

/**
 * Oncall has no external payment gateway to reconcile against (§Q of the
 * payments spec explicitly allows internal-consistency checks as the
 * documented fallback when no gateway is configured). These checks compare
 * JobPayment/PaymentAttempt state against the append-only wallet ledger,
 * which remains the single source of truth throughout.
 */
class ReconciliationService
{
    private const STALE_PAYMENT_HOURS = 72;

    /**
     * @return array{stale_payments: Collection, stale_attempts: Collection, duplicate_references: Collection, refund_mismatches: Collection}
     */
    public function detect(): array
    {
        return [
            'stale_payments' => $this->staleUnreleasedPayments(),
            'stale_attempts' => $this->staleAttempts(),
            'duplicate_references' => $this->duplicateReferences(),
            'refund_mismatches' => $this->refundMismatches(),
        ];
    }

    /** Run every check and upsert an idempotent ReconciliationFlag per finding. */
    public function flag(): Collection
    {
        $findings = $this->detect();
        $flags = collect();

        foreach ($findings['stale_payments'] as $payment) {
            $flags->push($this->upsert(ReconciliationCategory::StalePayment, $payment->id, null, "Job payment #{$payment->id} has been Paid but unreleased for over ".self::STALE_PAYMENT_HOURS.' hours.'));
        }

        foreach ($findings['stale_attempts'] as $attempt) {
            $flags->push($this->upsert(ReconciliationCategory::StaleAttempt, $attempt->job_payment_id, $attempt->id, "Payment attempt #{$attempt->id} is still Pending past its expiry."));
        }

        foreach ($findings['duplicate_references'] as $reference => $attempts) {
            foreach ($attempts as $attempt) {
                $flags->push($this->upsert(ReconciliationCategory::DuplicateReference, $attempt->job_payment_id, $attempt->id, "Gateway reference \"{$reference}\" is reused across multiple payment attempts."));
            }
        }

        foreach ($findings['refund_mismatches'] as $payment) {
            $flags->push($this->upsert(ReconciliationCategory::RefundMismatch, $payment->id, null, "Job payment #{$payment->id}'s refunded_amount cache does not match its posted refund ledger entries."));
        }

        return $flags;
    }

    public function resolve(ReconciliationFlag $flag, User $staff, string $notes): ReconciliationFlag
    {
        $flag->update([
            'status' => ReconciliationStatus::Resolved,
            'resolved_by' => $staff->id,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        AuditLog::create([
            'actor_id' => $staff->id,
            'event' => 'reconciliation_flag.resolved',
            'subject_type' => ReconciliationFlag::class,
            'subject_id' => $flag->id,
            'before_json' => null,
            'after_json' => $flag->fresh()->toArray(),
        ]);

        return $flag;
    }

    /** @return Collection<int, JobPayment> */
    private function staleUnreleasedPayments(): Collection
    {
        return JobPayment::query()
            ->where('status', JobPaymentStatus::Paid)
            ->where('confirmed_at', '<', now()->subHours(self::STALE_PAYMENT_HOURS))
            ->get();
    }

    /** @return Collection<int, PaymentAttempt> */
    private function staleAttempts(): Collection
    {
        return PaymentAttempt::query()
            ->where('status', PaymentAttemptStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();
    }

    /** @return Collection<string, Collection<int, PaymentAttempt>> */
    private function duplicateReferences(): Collection
    {
        return PaymentAttempt::query()
            ->whereNotNull('gateway_reference')
            ->get()
            ->groupBy('gateway_reference')
            ->filter(fn (Collection $attempts) => $attempts->pluck('job_payment_id')->unique()->count() > 1);
    }

    /** @return Collection<int, JobPayment> */
    private function refundMismatches(): Collection
    {
        return JobPayment::query()
            ->where('refunded_amount', '>', 0)
            ->get()
            ->filter(function (JobPayment $payment): bool {
                $posted = WalletTransaction::query()
                    ->where('reference_type', JobPayment::class)
                    ->where('reference_id', $payment->id)
                    ->where('type', WalletTransactionType::Refund)
                    ->where('status', WalletTransactionStatus::Posted)
                    ->sum('amount');

                $refundedFromLedger = bcmul((string) $posted, '-1', 2);

                return bccomp($refundedFromLedger, (string) $payment->refunded_amount, 2) !== 0;
            })
            ->values();
    }

    private function upsert(ReconciliationCategory $category, ?int $jobPaymentId, ?int $paymentAttemptId, string $description): ReconciliationFlag
    {
        return ReconciliationFlag::query()->updateOrCreate(
            ['category' => $category, 'job_payment_id' => $jobPaymentId, 'payment_attempt_id' => $paymentAttemptId],
            ['description' => $description, 'status' => ReconciliationStatus::Open],
        );
    }
}
