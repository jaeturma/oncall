<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptServiceRequestRequest;
use App\Http\Resources\Api\V1\JobResource;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestService;
use Illuminate\Http\JsonResponse;

class AcceptedServiceRequestController extends Controller
{
    public function __invoke(AcceptServiceRequestRequest $request, ServiceRequest $serviceRequest, ServiceRequestService $serviceRequests): JsonResponse
    {
        $job = $serviceRequests->accept($serviceRequest, $request->user(), (string) $request->validated('agreed_price'));

        return response()->json(['data' => new JobResource($job)], 201);
    }
}
