<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchProvidersRequest;
use App\Http\Resources\Api\V1\ProviderProfileResource;
use App\Models\LocationSetting;
use App\Services\ProviderSearchService;
use Illuminate\Http\JsonResponse;

class ProviderSearchController extends Controller
{
    public function __invoke(SearchProvidersRequest $request, ProviderSearchService $providerSearch): JsonResponse
    {
        $filters = $request->validated();
        $providers = $providerSearch->search($filters, $request->user(), $request);

        return response()->json([
            'data' => ProviderProfileResource::collection($providers),
            'meta' => [
                'current_page' => $providers->currentPage(),
                'last_page' => $providers->lastPage(),
                'total' => $providers->total(),
                'applied_radius_km' => isset($filters['radius_km']) ? (int) $filters['radius_km'] : null,
                // Explicit "expand search" affordance (Phase O §11) — never
                // silently widened. Only offered when a radius was actually
                // applied and the result set came back empty.
                'expand_radius_km' => isset($filters['radius_km']) && $providers->total() === 0
                    ? LocationSetting::current()->nextLargerRadius((int) $filters['radius_km'])
                    : null,
            ],
        ]);
    }
}
