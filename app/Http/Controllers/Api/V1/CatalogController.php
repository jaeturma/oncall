<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceCategoryResource;
use App\Models\LocationSetting;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ServiceCategory::query()
            ->where('active', true)
            ->with(['services' => fn ($query) => $query->where('active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        $locationSettings = LocationSetting::current();

        return response()->json([
            'data' => ServiceCategoryResource::collection($categories),
            // Bundled here rather than a new endpoint — this is already the
            // client's general-config bootstrap call. Never the map/
            // geocoding switches themselves (admin-only), just the public
            // search-radius policy the client needs to build a radius picker.
            'meta' => [
                'search_radius_choices' => $locationSettings->radiusChoices(),
                'default_search_radius_km' => $locationSettings->default_search_radius_km,
            ],
        ]);
    }
}
