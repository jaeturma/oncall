<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DisputeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveDisputeRequest;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DisputeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Dispute::class);

        return view('admin.disputes.index', [
            'open' => Dispute::query()
                ->with(['job.serviceRequest:id,title', 'raisedBy:id,name', 'againstUser:id,name'])
                ->whereIn('status', [DisputeStatus::Open, DisputeStatus::UnderReview])
                ->oldest('id')
                ->get(),
            'closed' => Dispute::query()
                ->with(['raisedBy:id,name', 'againstUser:id,name'])
                ->whereNotIn('status', [DisputeStatus::Open, DisputeStatus::UnderReview])
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function show(Dispute $dispute): View
    {
        Gate::authorize('view', $dispute);

        return view('admin.disputes.show', [
            'dispute' => $dispute->load(['job.serviceRequest', 'job.jobPayment', 'raisedBy', 'againstUser', 'resolver', 'enforcementCase']),
        ]);
    }

    public function update(ResolveDisputeRequest $request, Dispute $dispute, DisputeService $disputes): RedirectResponse
    {
        if ($request->input('action') === 'start_review') {
            $disputes->startReview($dispute, $request->user());

            return back()->with('status', 'Dispute moved to review.');
        }

        $disputes->resolve($dispute, $request->user(), [
            'action' => $request->string('action')->value(),
            'resolution' => $request->string('resolution')->trim()->value(),
            'refund_amount' => $request->input('refund_amount'),
            'open_enforcement' => $request->boolean('open_enforcement'),
            'enforce_against' => $request->input('enforce_against', 'respondent'),
        ]);

        return redirect()->route('admin.disputes.index')->with('status', 'Dispute resolved.');
    }
}
