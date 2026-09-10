<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEnforcementCaseRequest;
use App\Models\EnforcementCase;
use App\Services\EnforcementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EnforcementCaseController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', EnforcementCase::class);
        $cases = EnforcementCase::query()->with(['user:id,name,email,status', 'relatedReport.reporter:id,name'])->latest('id')->paginate(20);

        return view('admin.enforcement.index', ['cases' => $cases]);
    }

    public function show(EnforcementCase $enforcementCase): View
    {
        Gate::authorize('view', $enforcementCase);

        return view('admin.enforcement.show', ['case' => $enforcementCase->load(['user', 'relatedJob.serviceRequest.service', 'relatedReport.reporter', 'handler']), 'nextAction' => $enforcementCase->action?->value === 'SUSPENSION' ? null : app(EnforcementService::class)->nextAction($enforcementCase->action)]);
    }

    public function update(UpdateEnforcementCaseRequest $request, EnforcementCase $enforcementCase, EnforcementService $enforcement): RedirectResponse
    {
        $enforcement->apply($enforcementCase, $request->user(), $request->validated(), $request->ip(), $request->userAgent());

        return back()->with('status', 'Enforcement action recorded.');
    }
}
