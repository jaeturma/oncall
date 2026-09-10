<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DeclinedServiceRequestController extends Controller
{
    public function __invoke(Request $request, ServiceRequest $serviceRequest, ServiceRequestService $serviceRequests): RedirectResponse
    {
        Gate::authorize('respond', $serviceRequest);
        $serviceRequests->decline($serviceRequest, $request->user());

        return redirect()->route('service-requests.index')->with('status', 'Service request declined and returned to searching.');
    }
}
