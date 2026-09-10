<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProviderAvailabilityRequest;
use App\Models\ProviderProfile;
use Illuminate\Http\RedirectResponse;

class AvailabilityController extends Controller
{
    public function __invoke(UpdateProviderAvailabilityRequest $request, ProviderProfile $providerProfile): RedirectResponse
    {
        $providerProfile->update($request->validated());

        return back()->with('status', $providerProfile->available_now ? 'You are available now.' : 'You are unavailable.');
    }
}
