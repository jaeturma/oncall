<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProviderProfileRequest;
use App\Http\Requests\UpdateProviderProfileRequest;
use App\Http\Resources\Api\V1\ProviderProfileResource;
use App\Models\ProviderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show(Request $request): ProviderProfileResource
    {
        abort_unless($request->user()->role === UserRole::ServiceProvider, 403);
        $profile = $request->user()->providerProfile()->with(['province', 'municipality', 'providerServices.service'])->firstOrFail();

        return new ProviderProfileResource($profile);
    }

    public function store(StoreProviderProfileRequest $request): JsonResponse
    {
        $data = $request->validated();
        $profile = DB::transaction(function () use ($request, $data): ProviderProfile {
            $profile = $request->user()->providerProfile()->create($this->profileAttributes($data));
            $this->replaceServices($profile, $data['service_ids']);

            return $profile;
        });

        return response()->json(['data' => new ProviderProfileResource($profile->load(['province', 'municipality', 'providerServices.service']))], 201);
    }

    public function update(UpdateProviderProfileRequest $request, ProviderProfile $providerProfile): ProviderProfileResource
    {
        $data = $request->validated();
        DB::transaction(function () use ($providerProfile, $data): void {
            $providerProfile->update($this->profileAttributes($data));
            $this->replaceServices($providerProfile, $data['service_ids']);
        });

        return new ProviderProfileResource($providerProfile->fresh()->load(['province', 'municipality', 'providerServices.service']));
    }

    private function replaceServices(ProviderProfile $profile, array $serviceIds): void
    {
        $profile->providerServices()->delete();
        $profile->providerServices()->createMany(collect($serviceIds)->map(fn (int $serviceId): array => ['service_id' => $serviceId])->all());
    }

    private function profileAttributes(array $data): array
    {
        $attributes = Arr::except($data, 'service_ids');
        $credentials = array_values(array_filter($attributes['credentials_metadata'] ?? [], fn (?string $credential): bool => filled($credential)));
        $attributes['credentials_metadata'] = $credentials === [] ? null : $credentials;

        return $attributes;
    }
}
