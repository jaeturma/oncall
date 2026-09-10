<?php

namespace App\Models;

use App\Enums\JobPaymentStatus;
use App\Policies\JobPaymentPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['job_id', 'provider_id', 'gross_amount', 'platform_fee', 'net_amount', 'status', 'payment_method', 'payment_reference', 'earning_transaction_id', 'confirmed_by', 'confirmed_at', 'released_by', 'released_at', 'notes'])]
#[UsePolicy(JobPaymentPolicy::class)]
class JobPayment extends Model
{
    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'status' => JobPaymentStatus::class,
            'confirmed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function earningTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'earning_transaction_id');
    }
}
