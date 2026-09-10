<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'amount', 'status', 'payout_method', 'payout_reference', 'hold_transaction_id', 'accounting_reviewed_by', 'accounting_reviewed_at', 'budget_approved_by', 'budget_approved_at', 'disbursed_by', 'disbursed_at', 'notes'])]
class Withdrawal extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => WithdrawalStatus::class,
            'accounting_reviewed_at' => 'datetime',
            'budget_approved_at' => 'datetime',
            'disbursed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function holdTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'hold_transaction_id');
    }
}
