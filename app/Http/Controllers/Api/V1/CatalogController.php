<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceCategoryResource;
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

        return response()->json(['data' => ServiceCategoryResource::collection($categories)]);
    }
}
