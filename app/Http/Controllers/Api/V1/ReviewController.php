<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Job;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Job $job, ReviewService $reviews): JsonResponse
    {
        $review = $reviews->create($job, $request->user(), $request->validated());

        return response()->json(['data' => new ReviewResource($review)], 201);
    }
}
