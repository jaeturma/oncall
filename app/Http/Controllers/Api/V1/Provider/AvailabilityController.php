<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProviderAvailabilityRequest;
use App\Http\Resources\Api\V1\ProviderProfileResource;
use App\Models\ProviderProfile;

class AvailabilityController extends Controller
{
    public function __invoke(UpdateProviderAvailabilityRequest $request, ProviderProfile $providerProfile): ProviderProfileResource
    {
        $providerProfile->update($request->validated());

        return new ProviderProfileResource($providerProfile->fresh());
    }
}
