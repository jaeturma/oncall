<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
