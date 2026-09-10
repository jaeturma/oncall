<?php

namespace App\Services;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    /** @param array{rating: int, comment?: string|null} $attributes */
    public function create(Job $job, User $reviewer, array $attributes): Review
    {
        return DB::transaction(function () use ($job, $reviewer, $attributes): Review {
            $lockedJob = Job::whereKey($job)->lockForUpdate()->firstOrFail();
            abort_unless($lockedJob->status === JobStatus::Completed && in_array($reviewer->id, [$lockedJob->service_finder_id, $lockedJob->provider_id], true), 403);
            abort_if($lockedJob->reviews()->where('reviewer_id', $reviewer->id)->exists(), 409);
            $revieweeId = $reviewer->id === $lockedJob->service_finder_id ? $lockedJob->provider_id : $lockedJob->service_finder_id;
            $review = $lockedJob->reviews()->create(['reviewer_id' => $reviewer->id, 'reviewee_id' => $revieweeId, ...$attributes]);

            $reviewee = User::findOrFail($revieweeId);
            $stats = Review::where('reviewee_id', $revieweeId)->selectRaw('avg(rating) as average, count(*) as total')->first();
            $rating = round((float) $stats->average, 2);

            $reviewee->update(['rating_cached' => $rating, 'reviews_count' => (int) $stats->total]);
            $reviewee->providerProfile()->update(['rating_cached' => $rating]);

            return $review;
        });
    }
}
