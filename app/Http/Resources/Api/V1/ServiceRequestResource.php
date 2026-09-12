<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'urgency' => $this->urgency->value,
            'needed_at' => $this->needed_at,
            'budget_min' => $this->budget_min,
            'budget_max' => $this->budget_max,
            'status' => $this->status->value,
            'service' => $this->whenLoaded('service', fn () => ['id' => $this->service->id, 'name' => $this->service->name]),
            'province' => $this->whenLoaded('province', fn () => ['id' => $this->province->id, 'name' => $this->province->name]),
            'municipality' => $this->whenLoaded('municipality', fn () => ['id' => $this->municipality->id, 'name' => $this->municipality->name]),
            'service_finder' => $this->whenLoaded('serviceFinder', fn () => ['id' => $this->serviceFinder->id, 'name' => $this->serviceFinder->name]),
            'requested_provider' => $this->whenLoaded('requestedProvider', fn () => ['id' => $this->requestedProvider->id, 'name' => $this->requestedProvider->name]),
            'job_id' => $this->whenLoaded('job', fn () => $this->job?->id),
            'created_at' => $this->created_at,
        ];
    }
}
