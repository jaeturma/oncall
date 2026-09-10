<?php

namespace App\Services;

use App\Enums\JobPaymentStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\AuditLog;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Records the money side of a completed job. Oncall does not process payments,
 * so a job's earning only becomes withdrawable once the Service Finder confirms
 * they paid and Accounting confirms Oncall holds the funds.
 */
class JobPaymentService
{
    public function __construct(private readonly WalletLedger $ledger) {}

    /** Called when a job is marked completed. Idempotent. */
    public function openFor(Job $job): JobPayment
    {
        return JobPayment::query()->firstOrCreate(
            ['job_id' => $job->id],
            $this->split($job),
        );
    }

    public function confirmPaid(JobPayment $payment, User $finder, string $method, string $reference): JobPayment
    {
        return DB::transaction(function () use ($payment, $finder, $method, $reference): JobPayment {
            $locked = JobPayment::whereKey($payment)->lockForUpdate()->firstOrFail();

            if ($locked->status !== JobPaymentStatus::Pending) {
                throw new ConflictHttpException('This job payment has already been confirmed.');
            }

            $this->assertNoOpenDispute($locked);

            $before = $locked->toArray();

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
                'earning_transaction_id' => $earning->id,
                'confirmed_by' => $finder->id,
                'confirmed_at' => now(),
            ]);

            $this->audit($finder, 'job_payment.confirmed', $locked, $before);

            return $locked;
        });
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
                $this->ledger->post($locked->provider, WalletTransactionType::Adjustment, bcmul($refundAmount, '-1', 2), WalletTransactionStatus::Posted, 'Dispute partial refund: '.$reason, $locked);
            } elseif ($earning->status === WalletTransactionStatus::Pending) {
                $this->ledger->void($earning);
                $reduced = bcsub((string) $locked->net_amount, $refundAmount, 2);
                $replacement = $this->ledger->post($locked->provider, WalletTransactionType::JobEarning, $reduced, WalletTransactionStatus::Posted, 'Job earning after dispute partial refund', $locked);
                $locked->update(['earning_transaction_id' => $replacement->id]);
            }

            $locked->update(['status' => JobPaymentStatus::Released, 'released_by' => $staff->id, 'released_at' => now(), 'notes' => $reason]);
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
     * @return array<string, mixed>
     */
    private function split(Job $job): array
    {
        $gross = (string) $job->agreed_price;
        $percent = $this->platformPercent($job);
        $fee = bcmul($gross, bcdiv($percent, '100', 6), 2);

        return [
            'provider_id' => $job->provider_id,
            'gross_amount' => $gross,
            'platform_fee' => $fee,
            'net_amount' => bcsub($gross, $fee, 2),
            'status' => JobPaymentStatus::Pending,
        ];
    }

    private function platformPercent(Job $job): string
    {
        $override = $job->provider->accountType?->platform_commission_percent;

        return $override !== null
            ? (string) $override
            : (string) config('oncall.platform.commission_percent', 15);
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
