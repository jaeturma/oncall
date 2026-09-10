<?php

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Http\Requests\UpdateJobStatusRequest;
use App\Models\Job;
use App\Services\JobService;
use Illuminate\Http\RedirectResponse;

class JobStatusController extends Controller
{
    public function __invoke(UpdateJobStatusRequest $request, Job $job, JobService $jobs): RedirectResponse
    {
        $jobs->transition($job, $request->user(), JobStatus::from($request->validated('status')), $request->validated('notes'));

        return back()->with('status', 'Job status updated.');
    }
}
