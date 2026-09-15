<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A published (or, for the reviewer/reviewee/admin viewing their own
 * history, any-status) review. Never exposes reporter identity or
 * moderation notes — those live only in the admin-only
 * {@see ReviewReportResource}.
 */
class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status->value,
            // Every review here is inherently tied to a completed Oncall
            // job — see Review/ReviewService — so this is always true.
            // Does not mean Oncall verified every factual claim (Phase P §13).
            'verified_service' => true,
            'response' => $this->when($this->hasResponse(), fn () => [
                'body' => $this->response,
                'responded_at' => $this->responded_at,
            ]),
            'reviewer' => $this->whenLoaded('reviewer', fn () => ['id' => $this->reviewer->id, 'name' => $this->reviewer->name]),
            'reviewee' => $this->whenLoaded('reviewee', fn () => ['id' => $this->reviewee->id, 'name' => $this->reviewee->name]),
            'created_at' => $this->created_at,
        ];
    }
}
