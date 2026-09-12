<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AppealStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnforcementAppealRequest;
use App\Http\Resources\Api\V1\EnforcementCaseResource;
use App\Models\AuditLog;
use App\Models\EnforcementCase;

class EnforcementAppealController extends Controller
{
    public function __invoke(StoreEnforcementAppealRequest $request, EnforcementCase $enforcementCase): EnforcementCaseResource
    {
        $before = $enforcementCase->toArray();
        $enforcementCase->update(['appeal_status' => AppealStatus::Requested, 'appeal_reason' => $request->validated('appeal_reason')]);
        AuditLog::create(['actor_id' => $request->user()->id, 'event' => 'enforcement.appeal_requested', 'subject_type' => EnforcementCase::class, 'subject_id' => $enforcementCase->id, 'before_json' => $before, 'after_json' => $enforcementCase->fresh()->toArray(), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return new EnforcementCaseResource($enforcementCase->fresh());
    }
}
