<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Job::class);
        $jobs = Job::query()
            ->with(['serviceRequest.service:id,name', 'serviceFinder:id,name', 'provider:id,name'])
            ->when($request->user()->role === UserRole::ServiceFinder, fn ($query) => $query->whereBelongsTo($request->user(), 'serviceFinder'))
            ->when($request->user()->role === UserRole::ServiceProvider, fn ($query) => $query->whereBelongsTo($request->user(), 'provider'))
            ->latest('id')
            ->paginate(15);

        return view('jobs.index', ['jobs' => $jobs]);
    }

    public function show(Job $job): View
    {
        Gate::authorize('view', $job);
        $job->load(['serviceRequest.service', 'serviceRequest.province', 'serviceRequest.municipality', 'serviceFinder', 'provider', 'jobPayment', 'dispute', 'statusLogs' => fn ($query) => $query->with('changedBy:id,name')->latest('id'), 'messages' => fn ($query) => $query->with('sender:id,name')->oldest('id'), 'reviews' => fn ($query) => $query->with(['reviewer:id,name', 'reviewee:id,name'])->oldest('id')]);

        return view('jobs.show', ['job' => $job]);
    }
}
