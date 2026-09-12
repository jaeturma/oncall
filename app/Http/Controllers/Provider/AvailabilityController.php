<?php

namespace App\Http\Controllers\Provider;

use App\Enums\AvailabilityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProviderAvailabilityRequest;
use App\Models\ProviderProfile;
use Illuminate\Http\RedirectResponse;

class AvailabilityController extends Controller
{
    public function __invoke(UpdateProviderAvailabilityRequest $request, ProviderProfile $providerProfile): RedirectResponse
    {
        $providerProfile->update($request->validated());

        /** @var AvailabilityStatus $status */
        $status = $providerProfile->availability_status;

        return back()->with('status', 'Availability set to "'.$status->label().'".');
    }
}
