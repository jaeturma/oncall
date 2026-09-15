<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveReviewReportRequest;
use App\Models\AuditLog;
use App\Models\ReviewReport;
use App\Services\ReviewReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReviewReportController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('view-review-reports');
        $status = ReportStatus::tryFrom($request->string('status')->value()) ?? ReportStatus::Submitted;

        return view('admin.review-reports.index', [
            'reports' => ReviewReport::query()->with(['reporter', 'review.reviewee'])->where('status', $status)->oldest()->paginate(20),
            'status' => $status,
        ]);
    }

    public function show(ReviewReport $reviewReport): View
    {
        Gate::authorize('view-review-reports');

        return view('admin.review-reports.show', [
            'report' => $reviewReport->load(['reporter', 'reviewer', 'review.reviewer', 'review.reviewee', 'review.job']),
        ]);
    }

    public function update(ResolveReviewReportRequest $request, ReviewReport $reviewReport, ReviewReportService $reports): RedirectResponse
    {
        Gate::authorize('view-review-reports');
        $data = $request->validated();
        $before = ['status' => $reviewReport->status->value];

        $reports->resolve($reviewReport, $request->user(), ReportStatus::from($data['status']), $data['notes'] ?? null);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'event' => 'review_report.resolved',
            'subject_type' => ReviewReport::class,
            'subject_id' => $reviewReport->id,
            'before_json' => $before,
            'after_json' => ['status' => $data['status']],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.review-reports.index')->with('status', 'Report resolved.');
    }
}
