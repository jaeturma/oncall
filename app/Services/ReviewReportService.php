<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Enums\ReviewReportCategory;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewReportService
{
    /** @param array{category: ReviewReportCategory, description?: string|null} $attributes */
    public function report(Review $review, User $reporter, array $attributes): ReviewReport
    {
        // The (review_id, reporter_id) unique constraint is the real
        // guard against duplicate-report spam (Phase P §64); this
        // firstOrCreate just turns a race/double-tap into an idempotent
        // no-op instead of a 500 from the constraint violation.
        return ReviewReport::query()->firstOrCreate(
            ['review_id' => $review->id, 'reporter_id' => $reporter->id],
            [...$attributes, 'status' => ReportStatus::Submitted],
        );
    }

    /**
     * Admin-only resolution (route/gate-checked by the caller). Resolving a
     * report never itself changes the review's visibility — a moderator
     * hides/removes the review as a separate, explicit action via
     * ReviewService::moderate() (Phase P §20: reporting doesn't
     * automatically remove content).
     */
    public function resolve(ReviewReport $report, User $admin, ReportStatus $status, ?string $notes): ReviewReport
    {
        return DB::transaction(function () use ($report, $admin, $status, $notes): ReviewReport {
            $lockedReport = ReviewReport::whereKey($report)->lockForUpdate()->firstOrFail();
            $lockedReport->update([
                'status' => $status,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'moderation_notes' => $notes,
            ]);

            return $lockedReport;
        });
    }
}
