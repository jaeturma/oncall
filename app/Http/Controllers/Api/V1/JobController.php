<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\JobResource;
use App\Models\Job;
use App\Models\JobMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Job::class);
        $jobs = Job::query()
            ->with(['serviceRequest.service:id,name', 'serviceFinder:id,name', 'provider:id,name'])
            ->when($request->user()->role === UserRole::ServiceFinder, fn ($query) => $query->whereBelongsTo($request->user(), 'serviceFinder'))
            ->when($request->user()->role === UserRole::ServiceProvider, fn ($query) => $query->whereBelongsTo($request->user(), 'provider'))
            ->latest('id')
            ->paginate(15);

        return response()->json([
            'data' => JobResource::collection($jobs),
            'meta' => ['current_page' => $jobs->currentPage(), 'last_page' => $jobs->lastPage(), 'total' => $jobs->total()],
        ]);
    }

    public function show(Request $request, Job $job): JobResource
    {
        Gate::authorize('view', $job);
        $job->load(['serviceRequest.service', 'serviceRequest.province', 'serviceRequest.municipality', 'serviceFinder', 'provider', 'jobPayment', 'dispute', 'statusLogs' => fn ($query) => $query->with('changedBy:id,name')->latest('id'), 'messages' => fn ($query) => $query->with('sender:id,name')->oldest('id'), 'reviews' => fn ($query) => $query->with(['reviewer:id,name', 'reviewee:id,name'])->oldest('id')]);

        JobMessage::query()->where('job_id', $job->id)->whereNull('read_at')->where('sender_id', '!=', $request->user()->id)->update(['read_at' => now()]);

        return new JobResource($job);
    }
}
