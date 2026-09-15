<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReverseGeocodeRequest;
use App\Http\Resources\Api\V1\BarangayResource;
use App\Http\Resources\Api\V1\MunicipalityResource;
use App\Http\Resources\Api\V1\ProvinceResource;
use App\Models\Municipality;
use App\Models\Province;
use App\Services\GeocodingService;
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

    public function barangays(Municipality $municipality): JsonResponse
    {
        return response()->json(['data' => BarangayResource::collection($municipality->barangays()->orderBy('name')->get())]);
    }

    /**
     * Resolves a device coordinate pair down to province/municipality/
     * barangay so the client can prefill a "use my current location" search
     * without ever needing its own copy of the geography — always resolved
     * locally (see {@see GeocodingService}), never trusting the client for
     * eligibility-relevant location data.
     */
    public function reverseGeocode(ReverseGeocodeRequest $request, GeocodingService $geocoding): JsonResponse
    {
        $result = $geocoding->reverseGeocode($request->float('latitude'), $request->float('longitude'));

        return response()->json(['data' => [
            'province' => $result['province'] ? ['id' => $result['province']->id, 'name' => $result['province']->name] : null,
            'municipality' => $result['municipality'] ? ['id' => $result['municipality']->id, 'name' => $result['municipality']->name] : null,
            'barangay' => $result['barangay'] ? ['id' => $result['barangay']->id, 'name' => $result['barangay']->name] : null,
            'label' => $result['label'],
        ]]);
    }
}
