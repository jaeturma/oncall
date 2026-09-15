<?php

namespace App\Models;

use App\Enums\PaymentAttemptStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['job_payment_id', 'method', 'status', 'gateway', 'gateway_reference', 'idempotency_key', 'submitted_by', 'reviewed_by', 'reviewed_at', 'rejection_reason', 'expires_at'])]
class PaymentAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PaymentAttemptStatus::class,
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function jobPayment(): BelongsTo
    {
        return $this->belongsTo(JobPayment::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
