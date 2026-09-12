<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'reviewer' => $this->whenLoaded('reviewer', fn () => ['id' => $this->reviewer->id, 'name' => $this->reviewer->name]),
            'reviewee' => $this->whenLoaded('reviewee', fn () => ['id' => $this->reviewee->id, 'name' => $this->reviewee->name]),
            'created_at' => $this->created_at,
        ];
    }
}
