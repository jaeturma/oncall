<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateReviewRequest;
use App\Models\AuditLog;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Direct review-visibility moderation (hide/restore/remove) — a separate
 * action from resolving a specific report (Admin\ReviewReportController),
 * since a moderator may act on a review with or without an open report, and
 * resolving a report never by itself changes review visibility (Phase P §20).
 */
class ReviewModerationController extends Controller
{
    public function __invoke(ModerateReviewRequest $request, Review $review, ReviewService $reviews): RedirectResponse
    {
        Gate::authorize('moderate-reviews');
        $before = ['status' => $review->status->value];
        $status = ReviewStatus::from($request->validated('status'));

        $reviews->moderate($review, $status);

        // Notes are kept out of any public-facing summary — stored only in
        // this audit record's after_json, never on the review itself.
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'event' => 'review.moderated',
            'subject_type' => Review::class,
            'subject_id' => $review->id,
            'before_json' => $before,
            'after_json' => ['status' => $status->value, 'notes' => $request->validated('notes')],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Review updated.');
    }
}
