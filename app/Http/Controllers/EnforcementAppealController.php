<?php

namespace App\Http\Controllers;

use App\Enums\AppealStatus;
use App\Http\Requests\StoreEnforcementAppealRequest;
use App\Models\AuditLog;
use App\Models\EnforcementCase;
use Illuminate\Http\RedirectResponse;

class EnforcementAppealController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreEnforcementAppealRequest $request, EnforcementCase $enforcementCase): RedirectResponse
    {
        $before = $enforcementCase->toArray();
        $enforcementCase->update(['appeal_status' => AppealStatus::Requested, 'appeal_reason' => $request->validated('appeal_reason')]);
        AuditLog::create(['actor_id' => $request->user()->id, 'event' => 'enforcement.appeal_requested', 'subject_type' => EnforcementCase::class, 'subject_id' => $enforcementCase->id, 'before_json' => $before, 'after_json' => $enforcementCase->fresh()->toArray(), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return back()->with('status', 'Appeal submitted for admin review.');
    }
}
