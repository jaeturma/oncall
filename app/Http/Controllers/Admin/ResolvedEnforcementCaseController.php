<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveEnforcementCaseRequest;
use App\Models\EnforcementCase;
use App\Services\EnforcementService;
use Illuminate\Http\RedirectResponse;

class ResolvedEnforcementCaseController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ResolveEnforcementCaseRequest $request, EnforcementCase $enforcementCase, EnforcementService $enforcement): RedirectResponse
    {
        $enforcement->resolve($enforcementCase, $request->user(), $request->validated('resolution'), $request->ip(), $request->userAgent());

        return back()->with('status', 'Enforcement case resolved.');
    }
}
