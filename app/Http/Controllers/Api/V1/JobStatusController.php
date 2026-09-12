<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateJobStatusRequest;
use App\Http\Resources\Api\V1\JobResource;
use App\Models\Job;
use App\Services\JobService;

class JobStatusController extends Controller
{
    public function __invoke(UpdateJobStatusRequest $request, Job $job, JobService $jobs): JobResource
    {
        $jobs->transition($job, $request->user(), JobStatus::from($request->validated('status')), $request->validated('notes'));

        return new JobResource($job->fresh());
    }
}
