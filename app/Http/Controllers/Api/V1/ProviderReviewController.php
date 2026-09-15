<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexProviderReviewsRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\ReviewSetting;
use Illuminate\Http\JsonResponse;

class ProviderReviewController extends Controller
{
    public function __invoke(IndexProviderReviewsRequest $request, ProviderProfile $providerProfile): JsonResponse
    {
        $sort = $request->string('sort', 'newest')->value();

        $reviews = Review::query()
            ->where('reviewee_id', $providerProfile->user_id)
            ->where('status', ReviewStatus::Published)
            ->with('reviewer:id,name')
            ->when($sort === 'highest', fn ($query) => $query->orderByDesc('rating')->orderByDesc('created_at'))
            ->when($sort === 'lowest', fn ($query) => $query->orderBy('rating')->orderByDesc('created_at'))
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('created_at'))
            ->paginate(ReviewSetting::current()->reviews_per_page);

        return response()->json([
            'data' => ReviewResource::collection($reviews),
            'meta' => ['current_page' => $reviews->currentPage(), 'last_page' => $reviews->lastPage(), 'total' => $reviews->total()],
        ]);
    }
}
