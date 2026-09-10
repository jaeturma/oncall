<?php

namespace App\Models;

use App\Enums\AppealStatus;
use App\Enums\EnforcementAction;
use App\Enums\EnforcementCaseStatus;
use App\Enums\ReportCategory;
use App\Enums\ViolationSeverity;
use Database\Factories\EnforcementCaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'related_job_id', 'related_report_id', 'violation_category', 'severity', 'status', 'action', 'restricted_capabilities', 'starts_at', 'ends_at', 'handled_by', 'resolution', 'appeal_status', 'appeal_reason'])]
class EnforcementCase extends Model
{
    /** @use HasFactory<EnforcementCaseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['violation_category' => ReportCategory::class, 'severity' => ViolationSeverity::class, 'status' => EnforcementCaseStatus::class, 'action' => EnforcementAction::class, 'restricted_capabilities' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'appeal_status' => AppealStatus::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relatedJob(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'related_job_id');
    }

    public function relatedReport(): BelongsTo
    {
        return $this->belongsTo(UserReport::class, 'related_report_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
