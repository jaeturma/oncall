<?php

namespace App\Policies;

use App\Enums\JobStatus;
use App\Models\Dispute;
use App\Models\Job;
use App\Models\User;

class DisputePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, Dispute $dispute): bool
    {
        return $user->canAccessAdmin()
            || in_array($user->id, [$dispute->job->service_finder_id, $dispute->job->provider_id], true);
    }

    public function create(User $user, Job $job): bool
    {
        return in_array($user->id, [$job->service_finder_id, $job->provider_id], true)
            && in_array($job->status, [JobStatus::Accepted, JobStatus::OnTheWay, JobStatus::InProgress, JobStatus::Completed], true)
            && ! $job->dispute()->exists();
    }

    public function withdraw(User $user, Dispute $dispute): bool
    {
        return $dispute->raised_by === $user->id && $dispute->isOpen();
    }

    /** Admin starting review or resolving a dispute. */
    public function review(User $user, Dispute $dispute): bool
    {
        return $user->canAccessAdmin() && $dispute->isOpen();
    }
}
