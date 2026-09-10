<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\UserReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === UserRole::Admin, 403);
        $status = ReportStatus::tryFrom($request->string('status')->value());
        $reports = UserReport::query()->with(['reporter:id,name', 'reportedUser:id,name', 'job:id', 'enforcementCase:id,related_report_id,status'])->when($status, fn ($query) => $query->where('status', $status))->latest('id')->paginate(20)->withQueryString();

        return view('admin.reports.index', ['reports' => $reports]);
    }
}
