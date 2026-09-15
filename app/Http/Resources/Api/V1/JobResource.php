<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A Job only ever exists once a provider has accepted a service request
 * (`JobStatus::Accepted`+) — the authorized workflow state Phase O §17
 * requires before exact location may be disclosed. `latitude`/`longitude`/
 * `address_line` are therefore gated to only the two participants (the
 * assigned provider and the service finder who owns the request), never a
 * third party, and never appear at all before this point since a
 * `ServiceRequest` without a Job is served by {@see ServiceRequestResource},
 * which never includes them.
 */
class JobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isParticipant = $request->user()?->id === $this->provider_id || $request->user()?->id === $this->service_finder_id;

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'agreed_price' => $this->agreed_price,
            'accepted_at' => $this->accepted_at,
            'on_the_way_at' => $this->on_the_way_at,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'allowed_transitions' => $this->when($request->user() !== null, fn () => array_map(fn ($status) => $status->value, $this->allowedTransitionsFor($request->user()))),
            'service' => $this->whenLoaded('serviceRequest', fn () => $this->serviceRequest?->service ? ['id' => $this->serviceRequest->service->id, 'name' => $this->serviceRequest->service->name] : null),
            'province' => $this->whenLoaded('serviceRequest', fn () => $this->serviceRequest?->province ? ['id' => $this->serviceRequest->province->id, 'name' => $this->serviceRequest->province->name] : null),
            'municipality' => $this->whenLoaded('serviceRequest', fn () => $this->serviceRequest?->municipality ? ['id' => $this->serviceRequest->municipality->id, 'name' => $this->serviceRequest->municipality->name] : null),
            'barangay' => $this->whenLoaded('serviceRequest', fn () => $this->serviceRequest?->barangay ? ['id' => $this->serviceRequest->barangay->id, 'name' => $this->serviceRequest->barangay->name] : null),
            'latitude' => $this->when($isParticipant, fn () => $this->whenLoaded('serviceRequest', fn () => $this->serviceRequest?->latitude !== null ? (float) $this->serviceRequest->latitude : null)),
            'longitude' => $this->when($isParticipant, fn () => $this->whenLoaded('serviceRequest', fn () => $this->serviceRequest?->longitude !== null ? (float) $this->serviceRequest->longitude : null)),
            'address_line' => $this->when($isParticipant, fn () => $this->whenLoaded('serviceRequest', fn () => $this->serviceRequest?->address_line)),
            'service_finder' => $this->whenLoaded('serviceFinder', fn () => ['id' => $this->serviceFinder->id, 'name' => $this->serviceFinder->name]),
            'provider' => $this->whenLoaded('provider', fn () => ['id' => $this->provider->id, 'name' => $this->provider->name]),
            'payment' => JobPaymentResource::make($this->whenLoaded('jobPayment')),
            'dispute' => DisputeResource::make($this->whenLoaded('dispute')),
            'status_logs' => JobStatusLogResource::collection($this->whenLoaded('statusLogs')),
            'messages' => JobMessageResource::collection($this->whenLoaded('messages')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
