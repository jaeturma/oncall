<?php

namespace App\Http\Controllers;

use App\Enums\DisputeCategory;
use App\Http\Requests\StoreDisputeRequest;
use App\Models\Dispute;
use App\Models\Job;
use App\Services\DisputeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DisputeController extends Controller
{
    public function store(StoreDisputeRequest $request, Job $job, DisputeService $disputes): RedirectResponse
    {
        $disputes->open(
            $job,
            $request->user(),
            $request->enum('category', DisputeCategory::class),
            $request->string('description')->trim()->value(),
        );

        return redirect()->route('jobs.show', $job)->with('status', 'Dispute opened. The job payment is frozen while an admin reviews it.');
    }

    public function withdraw(Request $request, Dispute $dispute, DisputeService $disputes): RedirectResponse
    {
        Gate::authorize('withdraw', $dispute);
        $disputes->withdraw($dispute, $request->user());

        return redirect()->route('jobs.show', $dispute->job_id)->with('status', 'Dispute withdrawn.');
    }
}
