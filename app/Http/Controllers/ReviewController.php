<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Job;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Job $job, ReviewService $reviews): RedirectResponse
    {
        $reviews->create($job, $request->user(), $request->validated());

        return back()->with('status', 'Your review was recorded.');
    }
}
