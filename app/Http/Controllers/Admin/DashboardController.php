<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnforcementCaseStatus;
use App\Enums\JobStatus;
use App\Enums\ReportStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EnforcementCase;
use App\Models\Job;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use App\Models\UserReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->canAccessAdmin(), 403);

        return view('admin.dashboard', ['metrics' => [
            'users' => User::count(),
            'providers' => ProviderProfile::count(),
            'services' => Service::where('active', true)->count(),
            'active_jobs' => Job::whereIn('status', [JobStatus::Accepted, JobStatus::OnTheWay, JobStatus::InProgress])->count(),
            'pending_verifications' => ProviderDocument::where('status', VerificationStatus::Submitted)->count(),
            'open_reports' => UserReport::whereIn('status', [ReportStatus::Submitted, ReportStatus::UnderReview])->count(),
            'open_enforcement' => EnforcementCase::whereIn('status', [EnforcementCaseStatus::Open, EnforcementCaseStatus::UnderReview, EnforcementCaseStatus::Restricted, EnforcementCaseStatus::Suspended])->count(),
            'audit_events' => AuditLog::count(),
        ]]);
    }
}
