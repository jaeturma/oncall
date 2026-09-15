<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Review;
use App\Models\ReviewSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "My Reviews" / "Reviews Received" (Phase P §48) — the same authoritative
 * Review records as everywhere else, scoped to the authenticated user's own
 * history. Unlike the public provider-review listing, this includes every
 * status (including withdrawn/hidden) since it's the user's own data.
 */
class ReviewHistoryController extends Controller
{
    public function written(Request $request): JsonResponse
    {
        return $this->paginate($request, 'reviewer_id');
    }

    public function received(Request $request): JsonResponse
    {
        return $this->paginate($request, 'reviewee_id');
    }

    private function paginate(Request $request, string $column): JsonResponse
    {
        $reviews = Review::query()
            ->where($column, $request->user()->id)
            ->with(['reviewer:id,name', 'reviewee:id,name'])
            ->latest()
            ->paginate(ReviewSetting::current()->reviews_per_page);

        return response()->json([
            'data' => ReviewResource::collection($reviews),
            'meta' => ['current_page' => $reviews->currentPage(), 'last_page' => $reviews->lastPage(), 'total' => $reviews->total()],
        ]);
    }
}
