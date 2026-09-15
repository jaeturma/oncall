<?php

namespace App\Http\Resources\Api\V1;

use App\Services\ProviderReputationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A provider's public marketplace profile. Real name and contact details are
 * only ever included when the `user` relation was deliberately eager-loaded
 * by the caller for a verified Service Finder viewer — mirrors the reveal
 * rule in `ProviderController`/`ProviderSearchService` on the web. This
 * resource must never receive a `user` relation loaded for any other reason.
 *
 * Location privacy (Phase O §16): the provider's own `latitude`/`longitude`
 * columns are deliberately never read here. `area_marker` is a barangay/
 * municipality centroid computed by `ProviderSearchService` — already-public
 * reference geography, safe for map display — never the provider's exact
 * base coordinates, which stay server-side for matching only.
 */
class ProviderProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'bio' => $this->bio,
            'availability_status' => $this->availability_status->value,
            'available_now' => $this->available_now,
            'verification_status' => $this->verification_status->value,
            'rating' => $this->rating_cached,
            'completed_jobs' => $this->completed_jobs_cached,
            // Phase P: the fuller sanitized reputation payload (distribution,
            // verified-review count) — see ProviderReputationService::summary().
            // Never includes moderation data; `average_rating`/`rating_count`
            // here are the same honest figures as `rating`/reviews above,
            // just alongside the distribution for a profile/card display.
            'reputation' => app(ProviderReputationService::class)->summary($this->resource),
            'mobile_verified' => $this->mobile_verified ?? null,
            'email_verified' => $this->email_verified ?? null,
            'verified_document_types' => $this->verified_document_types ?? [],
            // Rounded to one decimal (~100m) before it ever leaves the
            // server — the precise computed value is only used internally
            // for radius filtering/sorting. Full-precision distance from
            // several known search origins is a triangulation vector
            // against a provider's real coordinates (Phase O §26/§31), so
            // this is a security control, not just a display nicety.
            'distance_km' => $this->distance_km !== null ? round($this->distance_km, 1) : null,
            'service_radius_km' => $this->service_radius_km,
            'area_marker' => $this->area_marker ?? null,
            'province' => $this->whenLoaded('province', fn () => ['id' => $this->province->id, 'name' => $this->province->name]),
            'municipality' => $this->whenLoaded('municipality', fn () => ['id' => $this->municipality->id, 'name' => $this->municipality->name]),
            'barangay' => $this->whenLoaded('barangay', fn () => $this->barangay ? ['id' => $this->barangay->id, 'name' => $this->barangay->name] : null),
            // The provider's own exact base coordinates — visible only to
            // that same provider (e.g. to prefill their own edit form),
            // never to a search viewer or any other user.
            'latitude' => $this->when($request->user()?->id === $this->user_id, fn () => $this->latitude !== null ? (float) $this->latitude : null),
            'longitude' => $this->when($request->user()?->id === $this->user_id, fn () => $this->longitude !== null ? (float) $this->longitude : null),
            'location_source' => $this->when($request->user()?->id === $this->user_id, fn () => $this->location_source?->value),
            'location_updated_at' => $this->when($request->user()?->id === $this->user_id, fn () => $this->location_updated_at),
            'services' => ProviderServiceResource::collection($this->whenLoaded('providerServices')),
        ];
    }
}
