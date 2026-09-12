<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service' => $this->whenLoaded('service', fn () => ['id' => $this->service->id, 'name' => $this->service->name]),
            'experience_text' => $this->experience_text,
            'rate_type' => $this->rate_type,
            'rate_from' => $this->rate_from,
            'rate_to' => $this->rate_to,
        ];
    }
}
