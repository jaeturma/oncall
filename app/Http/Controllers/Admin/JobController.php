<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canAccessAdmin(), 403);
        $status = JobStatus::tryFrom($request->string('status')->value());
        $jobs = Job::query()->with(['serviceRequest.service:id,name', 'serviceFinder:id,name', 'provider:id,name'])->when($status, fn ($query) => $query->where('status', $status))->latest('id')->paginate(20)->withQueryString();

        return view('admin.jobs.index', ['jobs' => $jobs]);
    }
}
