<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * A single inbox aggregating every booking's message thread. Oncall has no
 * standalone chat: messages always belong to a job, so this page is a
 * read-only index into those threads, sorted by most recent activity.
 */
class MessagesController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
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

        $conversations = new LengthAwarePaginator(
            $jobs->forPage($page, self::PER_PAGE)->values(),
            $jobs->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('messages.index', ['conversations' => $conversations]);
    }
}
