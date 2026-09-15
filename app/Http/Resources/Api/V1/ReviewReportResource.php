<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin/moderator-only view of a review report. Unlike ReviewResource, this
 * deliberately includes reporter identity and moderation notes — safe here
 * because this resource is never reachable by a marketplace viewer (see
 * ReviewReportPolicy: viewAny/view require canAccessAdmin()).
 */
class ReviewReportResource extends JsonResource
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
            'reporter' => $this->whenLoaded('reporter', fn () => ['id' => $this->reporter->id, 'name' => $this->reporter->name]),
            'review' => $this->whenLoaded('review', fn () => new ReviewResource($this->review)),
            'reviewed_by' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? ['id' => $this->reviewer->id, 'name' => $this->reviewer->name] : null),
            'reviewed_at' => $this->reviewed_at,
            'moderation_notes' => $this->moderation_notes,
            'created_at' => $this->created_at,
        ];
    }
}
