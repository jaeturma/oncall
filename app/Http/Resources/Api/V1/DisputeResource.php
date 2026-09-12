<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A dispute on the current viewer's own job. Internal `resolved_by` staff id is deliberately omitted, matching the web booking page. */
class DisputeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category->value,
            'description' => $this->description,
            'status' => $this->status->value,
            'refund_amount' => $this->refund_amount,
            'resolution' => $this->resolution,
            'resolved_at' => $this->resolved_at,
            'created_at' => $this->created_at,
        ];
    }
}
