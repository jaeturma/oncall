<?php

namespace App\Models;

use App\Enums\ReconciliationCategory;
use App\Enums\ReconciliationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['category', 'job_payment_id', 'payment_attempt_id', 'description', 'status', 'resolved_by', 'resolved_at', 'resolution_notes'])]
class ReconciliationFlag extends Model
{
    protected function casts(): array
    {
        return [
            'category' => ReconciliationCategory::class,
            'status' => ReconciliationStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function jobPayment(): BelongsTo
    {
        return $this->belongsTo(JobPayment::class);
    }

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
