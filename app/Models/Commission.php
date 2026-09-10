<?php

namespace App\Models;

use App\Enums\CommissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sponsor_user_id', 'sponsored_user_id', 'account_type_id', 'trigger', 'amount', 'status', 'wallet_transaction_id', 'approved_by', 'approved_at', 'reversal_reason'])]
class Commission extends Model
{
    public const TRIGGER_IDENTITY_VERIFIED = 'IDENTITY_VERIFIED';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => CommissionStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_user_id');
    }

    public function sponsoredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsored_user_id');
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
