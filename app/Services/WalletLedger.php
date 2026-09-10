<?php

namespace App\Services;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * The wallet is an append-only ledger. A user's balance is always derived by
 * summing ledger entries – it is never stored as a mutable number. Posted
 * entries are immutable; corrections are made by posting a Reversal entry.
 */
class WalletLedger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function post(
        User $user,
        WalletTransactionType $type,
        string $amount,
        WalletTransactionStatus $status = WalletTransactionStatus::Posted,
        ?string $description = null,
        ?Model $reference = null,
        array $meta = [],
    ): WalletTransaction {
        return $user->walletTransactions()->create([
            'type' => $type,
            'status' => $status,
            'amount' => $amount,
            'description' => $description,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'meta' => $meta ?: null,
        ]);
    }

    /** Balance the user can actually spend / withdraw, as a "0.00" string. */
    public function availableBalance(User $user): string
    {
        $sum = $user->walletTransactions()->posted()->sum('amount');

        return number_format((float) $sum, 2, '.', '');
    }

    /** Credits recorded but not yet released (e.g. unapproved commissions). */
    public function pendingBalance(User $user): string
    {
        $sum = $user->walletTransactions()
            ->where('status', WalletTransactionStatus::Pending)
            ->sum('amount');

        return number_format((float) $sum, 2, '.', '');
    }

    /** Release a pending entry that will never count (no reversing entry needed). */
    public function void(WalletTransaction $transaction): void
    {
        if ($transaction->status !== WalletTransactionStatus::Pending) {
            throw new RuntimeException('Only a pending wallet entry can be voided.');
        }

        $transaction->update(['status' => WalletTransactionStatus::Void]);
    }

    /** Move a pending credit into the available balance. */
    public function release(WalletTransaction $transaction): void
    {
        if ($transaction->status !== WalletTransactionStatus::Pending) {
            throw new RuntimeException('Only a pending wallet entry can be released.');
        }

        $transaction->update(['status' => WalletTransactionStatus::Posted]);
    }

    /** Post an opposing entry that cancels a previously posted amount. */
    public function reverse(WalletTransaction $transaction, string $reason): WalletTransaction
    {
        if ($transaction->status !== WalletTransactionStatus::Posted) {
            throw new RuntimeException('Only a posted wallet entry can be reversed.');
        }

        return $this->post(
            $transaction->user,
            WalletTransactionType::Reversal,
            bcmul((string) $transaction->amount, '-1', 2),
            WalletTransactionStatus::Posted,
            $reason,
            $transaction->reference,
            ['reverses_transaction_id' => $transaction->id],
        );
    }
}
