<?php

namespace App\Http\Controllers;

use App\Enums\EnforcementCaseStatus;
use App\Enums\ReportStatus;
use App\Enums\ViolationSeverity;
use App\Http\Requests\StoreUserReportRequest;
use App\Models\AuditLog;
use App\Models\EnforcementCase;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class UserReportController extends Controller
{
    public function store(StoreUserReportRequest $request, Job $job): RedirectResponse
    {
        DB::transaction(function () use ($request, $job): void {
            $reportedUserId = $job->service_finder_id === $request->user()->id ? $job->provider_id : $job->service_finder_id;
            $report = $request->user()->reportsMade()->create([
                ...$request->validated(),
                'reported_user_id' => $reportedUserId,
                'job_id' => $job->id,
                'status' => ReportStatus::Submitted,
            ]);
            $case = EnforcementCase::create([
                'user_id' => $reportedUserId,
                'related_job_id' => $job->id,
                'related_report_id' => $report->id,
                'violation_category' => $report->category,
                'severity' => ViolationSeverity::Low,
                'status' => EnforcementCaseStatus::Open,
            ]);
            AuditLog::create(['actor_id' => $request->user()->id, 'event' => 'safety.report_submitted', 'subject_type' => EnforcementCase::class, 'subject_id' => $case->id, 'before_json' => null, 'after_json' => $case->toArray(), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        });

        return back()->with('status', 'Safety report submitted for admin review. No enforcement is automatic.');
    }
}
