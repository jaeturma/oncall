<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Sponsor commissions are single level only: a sponsor earns from users they
 * directly sponsored, never from those users' own sponsees. Commission amounts
 * come from the sponsored user's configured Account Type, never from code.
 */
class CommissionEngine
{
    public function __construct(private readonly WalletLedger $ledger) {}

    /**
     * Trigger: a sponsored user's identity has been verified. Records the
     * commission as PENDING plus a matching pending (uncounted) wallet entry.
     */
    public function handleUserVerified(User $sponsoredUser): ?Commission
    {
        $sponsor = $sponsoredUser->sponsor;
        $accountType = $sponsoredUser->accountType;

        if ($sponsor === null || $accountType === null || ! $accountType->hasSponsorCommission()) {
            return null;
        }

        if ($sponsor->is($sponsoredUser)) {
            return null;
        }

        if (Commission::query()->where('sponsored_user_id', $sponsoredUser->id)->where('trigger', Commission::TRIGGER_IDENTITY_VERIFIED)->exists()) {
            return null;
        }

        $amount = $accountType->sponsorCommissionAmount();

        if (bccomp($amount, '0', 2) !== 1) {
            return null;
        }

        return DB::transaction(function () use ($sponsor, $sponsoredUser, $accountType, $amount): Commission {
            $commission = Commission::create([
                'sponsor_user_id' => $sponsor->id,
                'sponsored_user_id' => $sponsoredUser->id,
                'account_type_id' => $accountType->id,
                'trigger' => Commission::TRIGGER_IDENTITY_VERIFIED,
                'amount' => $amount,
                'status' => CommissionStatus::Pending,
            ]);

            $transaction = $this->ledger->post(
                $sponsor,
                WalletTransactionType::Commission,
                $amount,
                WalletTransactionStatus::Pending,
                'Sponsor commission for '.$sponsoredUser->name,
                $commission,
            );

            $commission->update(['wallet_transaction_id' => $transaction->id]);

            AuditLog::create([
                'actor_id' => null,
                'event' => 'commission.created',
                'subject_type' => Commission::class,
                'subject_id' => $commission->id,
                'before_json' => null,
                'after_json' => $commission->fresh()->toArray(),
            ]);

            return $commission;
        });
    }

    public function approve(Commission $commission, User $approver): Commission
    {
        return DB::transaction(function () use ($commission, $approver): Commission {
            $locked = Commission::whereKey($commission)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [CommissionStatus::Pending, CommissionStatus::Approved], true)) {
                throw new RuntimeException('Only a pending commission can be approved.');
            }

            $before = $locked->toArray();
            $locked->update([
                'status' => CommissionStatus::Available,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            if ($locked->walletTransaction && $locked->walletTransaction->status === WalletTransactionStatus::Pending) {
                $this->ledger->release($locked->walletTransaction);
            }

            $this->audit($approver, 'commission.approved', $locked, $before);

            return $locked;
        });
    }

    public function reverse(Commission $commission, User $actor, string $reason): Commission
    {
        return DB::transaction(function () use ($commission, $actor, $reason): Commission {
            $locked = Commission::whereKey($commission)->lockForUpdate()->firstOrFail();

            if ($locked->status === CommissionStatus::Reversed) {
                throw new RuntimeException('Commission is already reversed.');
            }

            $before = $locked->toArray();
            $transaction = $locked->walletTransaction;

            if ($transaction?->status === WalletTransactionStatus::Posted) {
                $this->ledger->reverse($transaction, 'Reversal: '.$reason);
            } elseif ($transaction?->status === WalletTransactionStatus::Pending) {
                $this->ledger->void($transaction);
            }

            $locked->update(['status' => CommissionStatus::Reversed, 'reversal_reason' => $reason]);
            $this->audit($actor, 'commission.reversed', $locked, $before);

            return $locked;
        });
    }

    /**
     * @param  array<string, mixed>  $before
     */
    private function audit(User $actor, string $event, Commission $commission, array $before): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'event' => $event,
            'subject_type' => Commission::class,
            'subject_id' => $commission->id,
            'before_json' => $before,
            'after_json' => $commission->fresh()->toArray(),
        ]);
    }
}
