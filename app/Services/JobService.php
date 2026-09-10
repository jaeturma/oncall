<?php

namespace App\Services;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JobService
{
    public function transition(Job $job, User $actor, JobStatus $targetStatus, ?string $notes): Job
    {
        return DB::transaction(function () use ($job, $actor, $targetStatus, $notes): Job {
            $lockedJob = Job::whereKey($job)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($targetStatus, $lockedJob->allowedTransitionsFor($actor), true), 409);
            $fromStatus = $lockedJob->status;
            $timestamps = match ($targetStatus) {
                JobStatus::OnTheWay => ['on_the_way_at' => now()],
                JobStatus::InProgress => ['started_at' => now()],
                JobStatus::Completed => ['completed_at' => now()],
                JobStatus::Cancelled => ['cancelled_at' => now()],
                default => [],
            };

            $lockedJob->update(['status' => $targetStatus, ...$timestamps]);
            $lockedJob->statusLogs()->create(['from_status' => $fromStatus, 'to_status' => $targetStatus, 'changed_by' => $actor->id, 'notes' => $notes]);

            if ($targetStatus === JobStatus::Completed) {
                $lockedJob->provider()->firstOrFail()->providerProfile()->increment('completed_jobs_cached');
            }

            return $lockedJob;
        });
    }
}
