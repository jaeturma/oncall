<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppealStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewEnforcementAppealRequest;
use App\Models\EnforcementCase;
use App\Services\EnforcementService;
use Illuminate\Http\RedirectResponse;

class ReviewedEnforcementAppealController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ReviewEnforcementAppealRequest $request, EnforcementCase $enforcementCase, EnforcementService $enforcement): RedirectResponse
    {
        $enforcement->reviewAppeal($enforcementCase, $request->user(), AppealStatus::from($request->validated('appeal_status')), $request->validated('resolution'), $request->ip(), $request->userAgent());

        return back()->with('status', 'Appeal review recorded.');
    }
}
