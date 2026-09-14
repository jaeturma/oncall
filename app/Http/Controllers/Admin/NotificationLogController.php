<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Http\Controllers\Controller;
use App\Models\NotificationDeliveryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NotificationLogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('view-notification-logs');

        $channel = NotificationChannel::tryFrom((string) $request->string('channel'));
        $status = NotificationDeliveryStatus::tryFrom((string) $request->string('status'));

        $logs = NotificationDeliveryLog::query()
            ->with(['user:id,name', 'device:id,platform'])
            ->when($channel, fn ($query) => $query->where('channel', $channel))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->filled('event_key'), fn ($query) => $query->where('event_key', $request->string('event_key')->value()))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->date('date')))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.notifications.logs', ['logs' => $logs, 'channel' => $channel, 'status' => $status]);
    }
}
