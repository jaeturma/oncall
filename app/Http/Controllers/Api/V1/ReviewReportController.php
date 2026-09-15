<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewReportRequest;
use App\Models\Review;
use App\Services\ReviewReportService;
use Illuminate\Http\JsonResponse;

class ReviewReportController extends Controller
{
    public function __invoke(StoreReviewReportRequest $request, Review $review, ReviewReportService $reports): JsonResponse
    {
        $reports->report($review, $request->user(), $request->validated());

        return response()->json(['message' => 'Report received.'], 201);
    }
}
