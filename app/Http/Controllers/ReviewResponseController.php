<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewResponseRequest;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class ReviewResponseController extends Controller
{
    public function __invoke(StoreReviewResponseRequest $request, Review $review, ReviewService $reviews): RedirectResponse
    {
        $reviews->respond($review, $request->user(), $request->string('response')->value());

        return back()->with('status', 'Your response was posted.');
    }
}
