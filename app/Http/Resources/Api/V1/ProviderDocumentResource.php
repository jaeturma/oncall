<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** The document owner's own verification submission. `private_path` (storage location) and `reviewed_by` (staff id) are never exposed, matching the web verification page. */
class ProviderDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type->value,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'reviewed_at' => $this->reviewed_at,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
