<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Http\Resources\Api\V1\ServiceRequestResource;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ServiceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ServiceRequest::class);
        $requests = ServiceRequest::query()
            ->with(['service:id,name', 'province:id,name', 'municipality:id,name', 'serviceFinder:id,name', 'requestedProvider:id,name'])
            ->when($request->user()->role === UserRole::ServiceFinder, fn ($query) => $query->whereBelongsTo($request->user(), 'serviceFinder'))
            ->when($request->user()->role === UserRole::ServiceProvider, fn ($query) => $query->whereBelongsTo($request->user(), 'requestedProvider'))
            ->latest()->paginate(15);

        return response()->json([
            'data' => ServiceRequestResource::collection($requests),
            'meta' => ['current_page' => $requests->currentPage(), 'last_page' => $requests->lastPage(), 'total' => $requests->total()],
        ]);
    }

    public function store(StoreServiceRequestRequest $request, ProviderProfile $providerProfile, ServiceRequestService $serviceRequests): JsonResponse
    {
        $serviceRequest = $serviceRequests->create($request->user(), $providerProfile, $request->validated());

        return response()->json(['data' => new ServiceRequestResource($serviceRequest)], 201);
    }

    public function show(ServiceRequest $serviceRequest): ServiceRequestResource
    {
        Gate::authorize('view', $serviceRequest);

        return new ServiceRequestResource($serviceRequest->load(['service', 'province', 'municipality', 'serviceFinder', 'requestedProvider', 'job']));
    }
}
