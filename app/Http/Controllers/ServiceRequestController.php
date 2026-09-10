<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ServiceRequest::class);
        $requests = ServiceRequest::query()
            ->with(['service:id,name', 'province:id,name', 'municipality:id,name', 'serviceFinder:id,name', 'requestedProvider:id,name'])
            ->when($request->user()->role === UserRole::ServiceFinder, fn ($query) => $query->whereBelongsTo($request->user(), 'serviceFinder'))
            ->when($request->user()->role === UserRole::ServiceProvider, fn ($query) => $query->whereBelongsTo($request->user(), 'requestedProvider'))
            ->latest()->paginate(15);

        return view('service-requests.index', ['requests' => $requests]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ProviderProfile $providerProfile): View
    {
        Gate::authorize('create', ServiceRequest::class);
        $providerProfile->load(['user', 'province', 'municipality', 'providerServices' => fn ($query) => $query->where('active', true)->with(['service' => fn ($query) => $query->where('active', true)])]);
        abort_unless($providerProfile->isRequestable(), 404);

        return view('service-requests.create', ['profile' => $providerProfile, 'providerServices' => $providerProfile->providerServices->filter->service]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceRequestRequest $request, ProviderProfile $providerProfile, ServiceRequestService $serviceRequests): RedirectResponse
    {
        $serviceRequest = $serviceRequests->create($request->user(), $providerProfile, $request->validated());

        return redirect()->route('service-requests.show', $serviceRequest)->with('status', 'Service request sent to the provider.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceRequest $serviceRequest): View
    {
        Gate::authorize('view', $serviceRequest);

        return view('service-requests.show', ['serviceRequest' => $serviceRequest->load(['service', 'province', 'municipality', 'serviceFinder', 'requestedProvider', 'job'])]);
    }
}
