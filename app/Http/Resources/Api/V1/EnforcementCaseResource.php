<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** The affected user's own enforcement case. `handled_by` (the staff actor id) is never exposed. */
class EnforcementCaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'violation_category' => $this->violation_category->value,
            'severity' => $this->severity->value,
            'status' => $this->status->value,
            'action' => $this->action?->value,
            'restricted_capabilities' => $this->restricted_capabilities,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'resolution' => $this->resolution,
            'appeal_status' => $this->appeal_status->value,
            'appeal_reason' => $this->appeal_reason,
            'created_at' => $this->created_at,
        ];
    }
}
