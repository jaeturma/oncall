<?php

namespace App\Services;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JobService
{
    public function __construct(
        private readonly JobPaymentService $jobPayments,
        private readonly Notifier $notifier,
    ) {}

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
                $this->jobPayments->openFor($lockedJob);
            }

            $recipientId = $actor->id === $lockedJob->service_finder_id ? $lockedJob->provider_id : $lockedJob->service_finder_id;
            $this->notifier->push(
                User::find($recipientId),
                'job.status_changed',
                'Booking updated: '.str($targetStatus->value)->replace('_', ' ')->title(),
                $actor->name.' set the job to "'.str($targetStatus->value)->replace('_', ' ')->title().'".',
                route('jobs.show', $lockedJob),
            );

            return $lockedJob;
        });
    }
}
