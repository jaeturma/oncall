<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\ReviewEligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobReviewEligibilityController extends Controller
{
    /**
     * Whether the authenticated user may review this job's other
     * participant (Phase P §47). Laravel decides — Flutter only presents
     * whatever this returns.
     */
    public function __invoke(Request $request, Job $job, ReviewEligibilityService $eligibility): JsonResponse
    {
        Gate::authorize('view', $job);
        $result = $eligibility->canReview($job, $request->user());

        return response()->json(['data' => [
            'can_review' => $result['can_review'],
            'reviewed' => $result['reviewed'],
            'expires_at' => $result['expires_at'],
        ]]);
    }
}
