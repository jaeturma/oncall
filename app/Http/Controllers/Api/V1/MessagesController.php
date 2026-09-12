<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ConversationResource;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/** Read-only inbox aggregating every booking's message thread; mirrors the web `MessagesController`. */
class MessagesController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $jobs = Job::query()
            ->where(fn ($query) => $query->where('service_finder_id', $user->id)->orWhere('provider_id', $user->id))
            ->whereHas('messages')
            ->with(['serviceRequest.service:id,name', 'serviceFinder:id,name', 'provider:id,name', 'latestMessage.sender:id,name'])
            ->withCount(['messages as unread_count' => fn ($query) => $query->whereNull('read_at')->where('sender_id', '!=', $user->id)])
            ->get()
            ->sortByDesc(fn (Job $job) => $job->latestMessage?->created_at)
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $conversations = $jobs->forPage($page, self::PER_PAGE)->values();

        return response()->json([
            'data' => ConversationResource::collection($conversations),
            'meta' => ['current_page' => $page, 'last_page' => (int) ceil(max($jobs->count(), 1) / self::PER_PAGE), 'total' => $jobs->count()],
        ]);
    }
}
