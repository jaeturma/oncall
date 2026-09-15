<?php

namespace App\Services;

use App\Enums\RefundStatus;
use App\Models\AuditLog;
use App\Models\JobPayment;
use App\Models\Refund;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\RefundRequest as GatewayRefundRequest;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * A request/approval workflow layered on top of {@see JobPaymentService}.
 * Every actual money movement still happens inside
 * {@see JobPaymentService::applyRefund()} — this service never touches the
 * wallet ledger directly, mirroring how {@see DisputeService} already
 * delegates dispute-driven refunds. This is the parallel, additive path for
 * non-dispute (customer-initiated or goodwill) refunds.
 */
class RefundService
{
    public function __construct(
        private readonly RefundEligibilityService $eligibility,
        private readonly JobPaymentService $jobPayments,
        private readonly PaymentManager $gateways,
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function request(JobPayment $payment, User $requester, string $amount, string $reason): Refund
    {
        return DB::transaction(function () use ($payment, $requester, $amount, $reason): Refund {
            $locked = JobPayment::whereKey($payment)->lockForUpdate()->firstOrFail();

            $check = $this->eligibility->eligible($locked, $amount);
            if (! $check['eligible']) {
                throw new ConflictHttpException($check['reason'] ?? 'This payment cannot be refunded.');
            }

            $refund = Refund::create([
                'job_payment_id' => $locked->id,
                'requested_by' => $requester->id,
                'amount' => $amount,
                'status' => RefundStatus::Requested,
                'reason' => $reason,
            ]);

            $this->audit($requester, 'refund.requested', $refund, null);

            return $refund;
        });
    }

    public function approve(Refund $refund, User $staff, ?string $notes = null): Refund
    {
        return DB::transaction(function () use ($refund, $staff, $notes): Refund {
            $locked = Refund::whereKey($refund)->lockForUpdate()->firstOrFail();

            if ($locked->status !== RefundStatus::Requested && $locked->status !== RefundStatus::UnderReview) {
                throw new ConflictHttpException('This refund request has already been decided.');
            }

            $payment = JobPayment::whereKey($locked->job_payment_id)->lockForUpdate()->firstOrFail();

            $check = $this->eligibility->eligible($payment, (string) $locked->amount, excludingRefundId: $locked->id);
            if (! $check['eligible']) {
                throw new ConflictHttpException($check['reason'] ?? 'This payment can no longer be refunded.');
            }

            $before = $locked->toArray();

            $this->gateways->active()->refund(new GatewayRefundRequest($payment, (string) $locked->amount));
            $this->jobPayments->applyRefund($payment, (string) $locked->amount, $staff, $notes ?? 'Refund approved');

            $ledgerEntry = $payment->fresh()->earningTransaction;

            $locked->update([
                'status' => RefundStatus::Completed,
                'decision_notes' => $notes,
                'reviewed_by' => $staff->id,
                'reviewed_at' => now(),
                'wallet_transaction_id' => $ledgerEntry?->id,
            ]);

            $this->audit($staff, 'refund.approved', $locked, $before);

            $this->notifications->dispatch(
                $payment->job->serviceFinder,
                'refund_completed',
                ['amount' => $locked->amount],
                ['screen' => 'job', 'id' => $payment->job_id],
                dedupKey: "refund_completed:refund:{$locked->id}",
            );

            return $locked;
        });
    }

    public function reject(Refund $refund, User $staff, string $reason): Refund
    {
        return DB::transaction(function () use ($refund, $staff, $reason): Refund {
            $locked = Refund::whereKey($refund)->lockForUpdate()->firstOrFail();

            if ($locked->status !== RefundStatus::Requested && $locked->status !== RefundStatus::UnderReview) {
                throw new ConflictHttpException('This refund request has already been decided.');
            }

            $before = $locked->toArray();

            $locked->update([
                'status' => RefundStatus::Rejected,
                'decision_notes' => $reason,
                'reviewed_by' => $staff->id,
                'reviewed_at' => now(),
            ]);

            $this->audit($staff, 'refund.rejected', $locked, $before);

            $requester = $locked->requester;
            if ($requester->id === $locked->jobPayment->job->service_finder_id) {
                $this->notifications->dispatch(
                    $requester,
                    'refund_rejected',
                    ['reason' => $reason],
                    ['screen' => 'job', 'id' => $locked->jobPayment->job_id],
                    dedupKey: "refund_rejected:refund:{$locked->id}",
                );
            }

            return $locked;
        });
    }

    /**
     * @param  array<string, mixed>|null  $before
     */
    private function audit(User $actor, string $event, Refund $refund, ?array $before): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'event' => $event,
            'subject_type' => Refund::class,
            'subject_id' => $refund->id,
            'before_json' => $before,
            'after_json' => $refund->fresh()->toArray(),
        ]);
    }
}
