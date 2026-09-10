<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Services\ServiceRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CancelledServiceRequestController extends Controller
{
    public function __invoke(Request $request, ServiceRequest $serviceRequest, ServiceRequestService $serviceRequests): RedirectResponse
    {
        Gate::authorize('cancel', $serviceRequest);
        $serviceRequests->cancel($serviceRequest, $request->user());

        return back()->with('status', 'Service request cancelled.');
    }
}
