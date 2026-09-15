<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewResponseRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;

class ReviewResponseController extends Controller
{
    public function __invoke(StoreReviewResponseRequest $request, Review $review, ReviewService $reviews): ReviewResource
    {
        $review = $reviews->respond($review, $request->user(), $request->string('response')->value());

        return new ReviewResource($review);
    }
}
