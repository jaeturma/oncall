<?php

namespace App\Models;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use Database\Factories\UserReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['reporter_id', 'reported_user_id', 'job_id', 'category', 'description', 'status', 'reviewed_by', 'reviewed_at'])]
class UserReport extends Model
{
    /** @use HasFactory<UserReportFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['category' => ReportCategory::class, 'status' => ReportStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function enforcementCase(): HasOne
    {
        return $this->hasOne(EnforcementCase::class, 'related_report_id');
    }
}
