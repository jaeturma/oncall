<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === UserRole::Admin, 403);
        $event = $request->string('event')->trim()->value();
        $logs = AuditLog::query()->with('actor:id,name')->when($event, fn ($query) => $query->where('event', $event))->latest('id')->paginate(30)->withQueryString();

        return view('admin.audit-logs.index', ['logs' => $logs]);
    }
}
