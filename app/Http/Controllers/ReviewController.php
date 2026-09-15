<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Job;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Job $job, ReviewService $reviews): RedirectResponse
    {
        $reviews->create($job, $request->user(), $request->validated());

        return back()->with('status', 'Your review was recorded.');
    }

    public function withdraw(Request $request, Review $review, ReviewService $reviews): RedirectResponse
    {
        Gate::authorize('withdraw', $review);
        $reviews->withdraw($review, $request->user());

        return back()->with('status', 'Your review was withdrawn.');
    }
}
