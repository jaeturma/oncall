<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MunicipalityResource;
use App\Http\Resources\Api\V1\ProvinceResource;
use App\Models\Province;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => ProvinceResource::collection(Province::query()->orderBy('name')->get())]);
    }

    public function municipalities(Province $province): JsonResponse
    {
        return response()->json(['data' => MunicipalityResource::collection($province->municipalities()->orderBy('name')->get())]);
    }
}
