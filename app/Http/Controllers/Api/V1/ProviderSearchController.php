<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchProvidersRequest;
use App\Http\Resources\Api\V1\ProviderProfileResource;
use App\Services\ProviderSearchService;
use Illuminate\Http\JsonResponse;

class ProviderSearchController extends Controller
{
    public function __invoke(SearchProvidersRequest $request, ProviderSearchService $providerSearch): JsonResponse
    {
        $providers = $providerSearch->search($request->validated(), $request->user(), $request);

        return response()->json([
            'data' => ProviderProfileResource::collection($providers),
            'meta' => ['current_page' => $providers->currentPage(), 'last_page' => $providers->lastPage(), 'total' => $providers->total()],
        ]);
    }
}
