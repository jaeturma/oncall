<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['job_payment_id', 'requested_by', 'amount', 'status', 'reason', 'decision_notes', 'reviewed_by', 'reviewed_at', 'wallet_transaction_id'])]
class Refund extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => RefundStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function jobPayment(): BelongsTo
    {
        return $this->belongsTo(JobPayment::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
