<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Enums\LocationSource;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProviderProfileRequest;
use App\Http\Requests\UpdateProviderProfileRequest;
use App\Http\Resources\Api\V1\ProviderProfileResource;
use App\Models\AuditLog;
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
        $profile = $request->user()->providerProfile()->with(['province', 'municipality', 'barangay', 'providerServices.service'])->firstOrFail();

        return new ProviderProfileResource($profile);
    }

    public function store(StoreProviderProfileRequest $request): JsonResponse
    {
        $data = $request->validated();
        $profile = DB::transaction(function () use ($request, $data): ProviderProfile {
            $profile = $request->user()->providerProfile()->create($this->profileAttributes($data));
            $this->replaceServices($profile, $data['service_ids']);
            $this->auditLocationIfChanged($request, $profile, before: null);

            return $profile;
        });

        return response()->json(['data' => new ProviderProfileResource($profile->load(['province', 'municipality', 'barangay', 'providerServices.service']))], 201);
    }

    public function update(UpdateProviderProfileRequest $request, ProviderProfile $providerProfile): ProviderProfileResource
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $providerProfile, $data): void {
            $before = Arr::only($providerProfile->getOriginal(), ['barangay_id', 'latitude', 'longitude']);
            $providerProfile->update($this->profileAttributes($data));
            $this->replaceServices($providerProfile, $data['service_ids']);
            $this->auditLocationIfChanged($request, $providerProfile, $before);
        });

        return new ProviderProfileResource($providerProfile->fresh()->load(['province', 'municipality', 'barangay', 'providerServices.service']));
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

        // Mobile has no separate "set from GPS" endpoint — coordinates
        // arrive here however the app captured them — so any submitted pair
        // is stamped MANUAL/now, matching what §23 asks be audited.
        if (array_key_exists('latitude', $attributes) && $attributes['latitude'] !== null) {
            $attributes['location_source'] = LocationSource::Manual;
            $attributes['location_updated_at'] = now();
        }

        return $attributes;
    }

    /**
     * Only the authenticated provider can reach this controller (enforced by
     * the FormRequest `authorize()` ownership checks), so this never records
     * *who* changed it beyond the actor — only *that* the base location
     * changed, without logging the coordinates themselves as free-form text.
     *
     * @param  array<string, mixed>|null  $before
     */
    private function auditLocationIfChanged(Request $request, ProviderProfile $profile, ?array $before): void
    {
        $after = Arr::only($profile->getAttributes(), ['barangay_id', 'latitude', 'longitude']);

        if ($before === $after) {
            return;
        }

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'event' => 'provider_location.updated',
            'subject_type' => ProviderProfile::class,
            'subject_id' => $profile->id,
            'before_json' => $before,
            'after_json' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
