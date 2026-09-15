<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewReportRequest;
use App\Models\Review;
use App\Services\ReviewReportService;
use Illuminate\Http\RedirectResponse;

class ReviewReportController extends Controller
{
    public function __invoke(StoreReviewReportRequest $request, Review $review, ReviewReportService $reports): RedirectResponse
    {
        $reports->report($review, $request->user(), $request->validated());

        return back()->with('status', 'Thanks — we received your report.');
    }
}
