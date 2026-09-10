<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestService;
use Illuminate\Http\RedirectResponse;

class AcceptedServiceRequestController extends Controller
{
    public function __invoke(AcceptServiceRequestRequest $request, ServiceRequest $serviceRequest, ServiceRequestService $serviceRequests): RedirectResponse
    {
        $job = $serviceRequests->accept($serviceRequest, $request->user(), (string) $request->validated('agreed_price'));

        return redirect()->route('jobs.show', $job)->with('status', 'Service request accepted and booking confirmed.');
    }
}
