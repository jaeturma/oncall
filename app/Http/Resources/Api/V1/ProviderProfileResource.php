<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A provider's public marketplace profile. Real name and contact details are
 * only ever included when the `user` relation was deliberately eager-loaded
 * by the caller for a verified Service Finder viewer — mirrors the reveal
 * rule in `ProviderController`/`ProviderSearchService` on the web. This
 * resource must never receive a `user` relation loaded for any other reason.
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
            'mobile_verified' => $this->mobile_verified ?? null,
            'email_verified' => $this->email_verified ?? null,
            'verified_document_types' => $this->verified_document_types ?? [],
            'distance_km' => $this->distance_km ?? null,
            'province' => $this->whenLoaded('province', fn () => ['id' => $this->province->id, 'name' => $this->province->name]),
            'municipality' => $this->whenLoaded('municipality', fn () => ['id' => $this->municipality->id, 'name' => $this->municipality->name]),
            'services' => ProviderServiceResource::collection($this->whenLoaded('providerServices')),
        ];
    }
}
