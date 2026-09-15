<?php

namespace App\Models;

use App\Enums\ReportStatus;
use App\Enums\ReviewReportCategory;
use Database\Factories\ReviewReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['review_id', 'reporter_id', 'category', 'description', 'status', 'reviewed_by', 'reviewed_at', 'moderation_notes'])]
class ReviewReport extends Model
{
    /** @use HasFactory<ReviewReportFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => ReviewReportCategory::class,
            'status' => ReportStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** The staff member who resolved this report, if any. */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
