<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ReviewSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "My Reviews" / "Reviews Received" (Phase P §48) — the same authoritative
 * Review records as everywhere else, scoped to the authenticated user's own
 * history (every status, since it's their own data).
 */
class ReviewHistoryController extends Controller
{
    public function written(Request $request): View
    {
        return view('reviews.mine', ['reviews' => $this->paginate($request, 'reviewer_id')]);
    }

    public function received(Request $request): View
    {
        return view('reviews.received', ['reviews' => $this->paginate($request, 'reviewee_id')]);
    }

    private function paginate(Request $request, string $column): LengthAwarePaginator
    {
        return Review::query()
            ->where($column, $request->user()->id)
            ->with(['reviewer:id,name', 'reviewee:id,name', 'job.serviceRequest.service:id,name'])
            ->latest()
            ->paginate(ReviewSetting::current()->reviews_per_page)
            ->withQueryString();
    }
}
