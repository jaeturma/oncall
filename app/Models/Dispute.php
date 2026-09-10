<?php

namespace App\Models;

use App\Enums\DisputeCategory;
use App\Enums\DisputeStatus;
use App\Enums\JobStatus;
use App\Policies\DisputePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['job_id', 'raised_by', 'against_user_id', 'category', 'description', 'status', 'job_prior_status', 'refund_amount', 'resolution', 'resolved_by', 'resolved_at', 'enforcement_case_id'])]
#[UsePolicy(DisputePolicy::class)]
class Dispute extends Model
{
    protected function casts(): array
    {
        return [
            'category' => DisputeCategory::class,
            'status' => DisputeStatus::class,
            'job_prior_status' => JobStatus::class,
            'refund_amount' => 'decimal:2',
            'resolved_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function againstUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'against_user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function enforcementCase(): BelongsTo
    {
        return $this->belongsTo(EnforcementCase::class);
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }
}
