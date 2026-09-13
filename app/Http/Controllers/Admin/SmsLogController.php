<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SmsDeliveryStatus;
use App\Http\Controllers\Controller;
use App\Models\SmsDeliveryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SmsLogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('view-sms-logs');
        $status = SmsDeliveryStatus::tryFrom($request->string('status')->value());

        $logs = SmsDeliveryLog::query()
            ->with('user:id,name')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->date('date')))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.sms-logs.index', ['logs' => $logs, 'status' => $status]);
    }
}
