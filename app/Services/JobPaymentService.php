<?php

namespace App\Services;

use App\Enums\JobPaymentStatus;
use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentMethod;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\AuditLog;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\PaymentSetting;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaymentRequest;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Records the money side of a completed job. Oncall does not process payments,
 * so a job's earning only becomes withdrawable once the Service Finder confirms
 * they paid and Accounting confirms Oncall holds the funds.
 */
class JobPaymentService
{
    public function __construct(
        private readonly WalletLedger $ledger,
        private readonly NotificationDispatcher $notifications,
        private readonly FeeCalculationService $fees,
        private readonly PaymentManager $gateways,
    ) {}

    /** Called when a job is marked completed. Idempotent. */
    public function openFor(Job $job): JobPayment
    {
        return JobPayment::query()->firstOrCreate(
            ['job_id' => $job->id],
            $this->fees->split($job),
        );
    }

    /**
     * A payment can be confirmed while Pending (first attempt) or Reversed
     * (a safe retry after a chargeback/dispute reversal, without discarding
     * the earlier attempts or ledger entries).
     */
    public function confirmPaid(JobPayment $payment, User $finder, string $method, string $reference): JobPayment
    {
        $gateway = $this->gateways->active();
        $methodEnum = PaymentMethod::tryFrom(strtoupper($method)) ?? PaymentMethod::Other;

        return DB::transaction(function () use ($payment, $finder, $method, $reference, $gateway, $methodEnum): JobPayment {
            $locked = JobPayment::whereKey($payment)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [JobPaymentStatus::Pending, JobPaymentStatus::Reversed], true)) {
                throw new ConflictHttpException('This job payment has already been confirmed.');
            }

            $this->assertNoOpenDispute($locked);

            $before = $locked->toArray();

            $result = $gateway->charge(new PaymentRequest($locked, $methodEnum, (string) $locked->net_amount, $reference));

            // The Manual gateway never fails, so this branch is unreachable today.
            // A future real gateway integration would still want the rejected
            // attempt recorded outside this transaction before throwing.
            $locked->attempts()->create([
                'method' => $method,
                'status' => $result->successful ? PaymentAttemptStatus::Verified : PaymentAttemptStatus::Rejected,
                'gateway' => $result->gateway,
                'gateway_reference' => $result->gatewayReference,
                'submitted_by' => $finder->id,
                'rejection_reason' => $result->failureReason,
            ]);

            if (! $result->successful) {
                throw new ConflictHttpException($result->failureReason ?? 'Payment could not be confirmed.');
            }

            $earning = $this->ledger->post(
                $locked->provider,
                WalletTransactionType::JobEarning,
                (string) $locked->net_amount,
                WalletTransactionStatus::Pending,
                'Job earning (after PHP '.$locked->platform_fee.' platform fee)',
                $locked,
            );

            $locked->update([
                'status' => JobPaymentStatus::Paid,
                'payment_method' => $method,
                'payment_reference' => $reference,
                'receipt_number' => $locked->receipt_number ?? $this->receiptNumber($locked),
                'earning_transaction_id' => $earning->id,
                'confirmed_by' => $finder->id,
                'confirmed_at' => now(),
            ]);

            $this->audit($finder, 'job_payment.confirmed', $locked, $before);
            $this->notifications->dispatch(
                $locked->provider,
                'job_payment_confirmed',
                ['amount' => $locked->net_amount],
                ['screen' => 'job', 'id' => $locked->job_id],
                dedupKey: "job_payment_confirmed:job_payment:{$locked->id}",
            );

            return $locked;
        });
    }

    private function receiptNumber(JobPayment $payment): string
    {
        return sprintf('%s-%06d', PaymentSetting::current()->receipt_prefix, $payment->id);
    }

    public function release(JobPayment $payment, User $staff): JobPayment
    {
        return DB::transaction(function () use ($payment, $staff): JobPayment {
            $locked = JobPayment::whereKey($payment)->lockForUpdate()->firstOrFail();

            if ($locked->status !== JobPaymentStatus::Paid) {
                throw new ConflictHttpException('Only a confirmed job payment can be released.');
            }

            $this->assertNoOpenDispute($locked);
            $before = $locked->toArray();

            if ($locked->earningTransaction?->status === WalletTransactionStatus::Pending) {
                $this->ledger->release($locked->earningTransaction);
            }

            $locked->update(['status' => JobPaymentStatus::Released, 'released_by' => $staff->id, 'released_at' => now()]);
            $this->audit($staff, 'job_payment.released', $locked, $before);
            $this->notifications->dispatch(
                $locked->provider,
                'job_payment_released',
                ['amount' => $locked->net_amount],
                ['screen' => 'wallet'],
                dedupKey: "job_payment_released:job_payment:{$locked->id}",
            );

            return $locked;
        });
    }

    public function reverse(JobPayment $payment, User $staff, string $reason): JobPayment
    {
        return DB::transaction(function () use ($payment, $staff, $reason): JobPayment {
            $locked = JobPayment::whereKey($payment)->lockForUpdate()->firstOrFail();

            if ($locked->status === JobPaymentStatus::Reversed) {
                throw new ConflictHttpException('This job payment is already reversed.');
            }

            $before = $locked->toArray();
            $earning = $locked->earningTransaction;

            if ($earning?->status === WalletTransactionStatus::Posted) {
                $this->ledger->reverse($earning, 'Job payment reversal: '.$reason);
            } elseif ($earning?->status === WalletTransactionStatus::Pending) {
                $this->ledger->void($earning);
            }

            $locked->update(['status' => JobPaymentStatus::Reversed, 'notes' => $reason]);
            $this->audit($staff, 'job_payment.reversed', $locked, $before);
            $this->notifications->dispatch(
                $locked->provider,
                'earning_reversed',
                ['amount' => $locked->net_amount, 'reason' => $reason],
                ['screen' => 'wallet'],
                dedupKey: "earning_reversed:job_payment:{$locked->id}",
            );

            return $locked;
        });
    }

    /**
     * Dispute resolution: reduce the provider's net for this job by $refundAmount.
     */
    public function applyRefund(JobPayment $payment, string $refundAmount, User $staff, string $reason): void
    {
        DB::transaction(function () use ($payment, $refundAmount, $staff, $reason): void {
            $locked = JobPayment::whereKey($payment)->lockForUpdate()->firstOrFail();
            $earning = $locked->earningTransaction;

            if ($earning === null || bccomp($refundAmount, '0', 2) !== 1) {
                return;
            }

            if ($earning->status === WalletTransactionStatus::Posted) {
                $this->ledger->post($locked->provider, WalletTransactionType::Refund, bcmul($refundAmount, '-1', 2), WalletTransactionStatus::Posted, 'Dispute partial refund: '.$reason, $locked);
            } elseif ($earning->status === WalletTransactionStatus::Pending) {
                $this->ledger->void($earning);
                $reduced = bcsub((string) $locked->net_amount, $refundAmount, 2);
                $replacement = $this->ledger->post($locked->provider, WalletTransactionType::JobEarning, $reduced, WalletTransactionStatus::Posted, 'Job earning after dispute partial refund', $locked);
                $locked->update(['earning_transaction_id' => $replacement->id]);
            }

            $locked->update([
                'status' => JobPaymentStatus::Released,
                'released_by' => $staff->id,
                'released_at' => now(),
                'notes' => $reason,
                'refunded_amount' => bcadd((string) $locked->refunded_amount, $refundAmount, 2),
            ]);
            $this->audit($staff, 'job_payment.partial_refund', $locked, []);
        });
    }

    private function assertNoOpenDispute(JobPayment $payment): void
    {
        if ($payment->job->dispute?->isOpen()) {
            throw new ConflictHttpException('This job has an open dispute; the payment is frozen until it is resolved.');
        }
    }

    /**
     * @param  array<string, mixed>  $before
     */
    private function audit(User $actor, string $event, JobPayment $payment, array $before): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'event' => $event,
            'subject_type' => JobPayment::class,
            'subject_id' => $payment->id,
            'before_json' => $before,
            'after_json' => $payment->fresh()->toArray(),
        ]);
    }
}
