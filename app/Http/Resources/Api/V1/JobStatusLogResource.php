<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobStatusLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status?->value,
            'to_status' => $this->to_status->value,
            'notes' => $this->notes,
            'changed_by' => $this->whenLoaded('changedBy', fn () => $this->changedBy?->name),
            'created_at' => $this->created_at,
        ];
    }
}
