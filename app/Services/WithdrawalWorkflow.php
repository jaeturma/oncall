<?php

namespace App\Services;

use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Cashout workflow: Requested -> Accounting Review -> Budget Approval ->
 * For Disbursement -> Disbursed -> Completed. The requested amount is held on
 * the ledger the moment the request is made so it cannot be withdrawn twice.
 * No money leaves without a matching, auditable ledger entry.
 */
class WithdrawalWorkflow
{
    public function __construct(
        private readonly WalletLedger $ledger,
        private readonly Notifier $notifier,
    ) {}

    public function request(User $user, string $amount, string $payoutMethod, string $payoutReference): Withdrawal
    {
        return DB::transaction(function () use ($user, $amount, $payoutMethod, $payoutReference): Withdrawal {
            // Serialize concurrent requests for this user's wallet.
            WalletTransaction::query()->where('user_id', $user->id)->lockForUpdate()->get();

            if (bccomp($amount, '0.01', 2) < 0) {
                throw new ConflictHttpException('Withdrawal amount must be greater than zero.');
            }

            if (bccomp($amount, $this->ledger->availableBalance($user), 2) === 1) {
                throw new ConflictHttpException('Withdrawal amount exceeds the available wallet balance.');
            }

            $hold = $this->ledger->post(
                $user,
                WalletTransactionType::WithdrawalHold,
                bcmul($amount, '-1', 2),
                description: 'Reserved for withdrawal request',
            );

            $withdrawal = $user->withdrawals()->create([
                'amount' => $amount,
                'status' => WithdrawalStatus::AccountingReview,
                'payout_method' => $payoutMethod,
                'payout_reference' => $payoutReference,
                'hold_transaction_id' => $hold->id,
            ]);

            $this->audit(null, 'withdrawal.requested', $withdrawal, null);

            return $withdrawal;
        });
    }

    /**
     * @param  'approve'|'return'|'reject'  $decision
     */
    public function advance(Withdrawal $withdrawal, User $actor, string $decision, ?string $notes = null): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $actor, $decision, $notes): Withdrawal {
            $locked = Withdrawal::whereKey($withdrawal)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isOpen()) {
                throw new ConflictHttpException('This withdrawal is no longer open for review.');
            }

            $requiredRole = $locked->status->actingRole();
            if (! $actor->canAccessAdmin() && $actor->role !== $requiredRole) {
                throw new ConflictHttpException('This step must be handled by '.$requiredRole?->value.'.');
            }

            $before = $locked->toArray();
            $stamp = trim((string) $notes) !== '' ? trim((string) $notes) : null;

            if ($decision === 'reject' || $decision === 'return') {
                $this->releaseHold($locked, $decision === 'reject' ? 'Withdrawal rejected' : 'Withdrawal returned to requester');
                $locked->update([
                    'status' => $decision === 'reject' ? WithdrawalStatus::Rejected : WithdrawalStatus::Returned,
                    'notes' => $stamp,
                ]);
                $this->audit($actor, 'withdrawal.'.$decision.'ed', $locked, $before);
                $this->notify($locked, $decision === 'reject' ? 'was rejected' : 'was returned for correction — the amount is back in your wallet');

                return $locked;
            }

            match ($locked->status) {
                WithdrawalStatus::Requested, WithdrawalStatus::AccountingReview => $locked->update([
                    'status' => WithdrawalStatus::BudgetApproval,
                    'accounting_reviewed_by' => $actor->id,
                    'accounting_reviewed_at' => now(),
                    'notes' => $stamp,
                ]),
                WithdrawalStatus::BudgetApproval => $locked->update([
                    'status' => WithdrawalStatus::ForDisbursement,
                    'budget_approved_by' => $actor->id,
                    'budget_approved_at' => now(),
                    'notes' => $stamp,
                ]),
                WithdrawalStatus::ForDisbursement => $this->disburse($locked, $actor, $stamp),
                default => throw new ConflictHttpException('Unexpected withdrawal state.'),
            };

            $this->audit($actor, 'withdrawal.advanced', $locked, $before);
            $this->notify($locked, $locked->status === WithdrawalStatus::Completed
                ? 'has been disbursed'
                : 'advanced to '.str($locked->status->value)->replace('_', ' ')->title());

            return $locked;
        });
    }

    private function notify(Withdrawal $withdrawal, string $phrase): void
    {
        $this->notifier->push(
            $withdrawal->user,
            'withdrawal.updated',
            'Withdrawal update',
            'Your PHP '.$withdrawal->amount.' withdrawal '.$phrase.'.',
            route('withdrawals.index'),
        );
    }

    public function cancel(Withdrawal $withdrawal, User $owner): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $owner): Withdrawal {
            $locked = Withdrawal::whereKey($withdrawal)->lockForUpdate()->firstOrFail();

            if ($locked->user_id !== $owner->id || $locked->status !== WithdrawalStatus::AccountingReview) {
                throw new ConflictHttpException('This withdrawal can no longer be cancelled.');
            }

            $before = $locked->toArray();
            $this->releaseHold($locked, 'Withdrawal cancelled by requester');
            $locked->update(['status' => WithdrawalStatus::Cancelled]);
            $this->audit($owner, 'withdrawal.cancelled', $locked, $before);

            return $locked;
        });
    }

    private function disburse(Withdrawal $withdrawal, User $cashier, ?string $notes): void
    {
        // Post the actual withdrawal debit and release the original hold – net
        // effect stays at -amount, but the ledger now shows a real Withdrawal.
        $this->ledger->post($withdrawal->user, WalletTransactionType::Withdrawal, bcmul((string) $withdrawal->amount, '-1', 2), description: 'Withdrawal disbursed', reference: $withdrawal);
        $this->ledger->post($withdrawal->user, WalletTransactionType::WithdrawalRelease, (string) $withdrawal->amount, description: 'Released withdrawal hold on disbursement', reference: $withdrawal);

        $withdrawal->update([
            'status' => WithdrawalStatus::Completed,
            'disbursed_by' => $cashier->id,
            'disbursed_at' => now(),
            'notes' => $notes,
        ]);
    }

    private function releaseHold(Withdrawal $withdrawal, string $reason): void
    {
        if ($withdrawal->hold_transaction_id === null) {
            return;
        }

        $this->ledger->post(
            $withdrawal->user,
            WalletTransactionType::WithdrawalRelease,
            (string) $withdrawal->amount,
            description: $reason,
            reference: $withdrawal,
            meta: ['releases_transaction_id' => $withdrawal->hold_transaction_id],
        );

        $withdrawal->update(['hold_transaction_id' => null]);
    }

    /**
     * @param  array<string, mixed>|null  $before
     */
    private function audit(?User $actor, string $event, Withdrawal $withdrawal, ?array $before): void
    {
        AuditLog::create([
            'actor_id' => $actor?->id,
            'event' => $event,
            'subject_type' => Withdrawal::class,
            'subject_id' => $withdrawal->id,
            'before_json' => $before,
            'after_json' => $withdrawal->fresh()->toArray(),
        ]);
    }
}
