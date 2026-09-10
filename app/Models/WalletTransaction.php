<?php

namespace App\Models;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An append-only wallet ledger entry. `amount` is signed (credits positive,
 * debits negative) and never mutated after it is Posted; corrections are made
 * by posting a new Reversal / Adjustment entry.
 */
#[Fillable(['user_id', 'type', 'status', 'amount', 'description', 'reference_type', 'reference_id', 'meta'])]
class WalletTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'type' => WalletTransactionType::class,
            'status' => WalletTransactionStatus::class,
            'amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /** Entries that count toward the available balance. */
    public function scopePosted(Builder $query): void
    {
        $query->where('status', WalletTransactionStatus::Posted);
    }
}
