<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DeclinedServiceRequestController extends Controller
{
    public function __invoke(Request $request, ServiceRequest $serviceRequest, ServiceRequestService $serviceRequests): ServiceRequestResource
    {
        Gate::authorize('respond', $serviceRequest);
        $serviceRequests->decline($serviceRequest, $request->user());

        return new ServiceRequestResource($serviceRequest->fresh());
    }
}
