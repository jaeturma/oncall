<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'data' => NotificationResource::collection($notifications),
            'meta' => ['current_page' => $notifications->currentPage(), 'last_page' => $notifications->lastPage(), 'total' => $notifications->total()],
        ]);
    }

    public function read(Request $request, DatabaseNotification $notification): JsonResponse
    {
        abort_unless($this->owns($request, $notification), 403);
        $notification->markAsRead();

        return response()->json(['data' => new NotificationResource($notification->fresh())]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    private function owns(Request $request, DatabaseNotification $notification): bool
    {
        return $notification->notifiable_type === $request->user()->getMorphClass()
            && (string) $notification->notifiable_id === (string) $request->user()->getKey();
    }
}
