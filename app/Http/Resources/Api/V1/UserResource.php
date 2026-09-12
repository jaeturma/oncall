<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** The authenticated mobile user's own account. Never used to represent another user. */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'initials' => $this->initials,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'email_verified' => $this->hasVerifiedEmail(),
            'mobile_verified' => $this->isMobileVerified(),
            'identity_verification_status' => $this->identity_verification_status?->value,
            'identity_verified' => $this->isIdentityVerified(),
            'rating' => $this->rating_cached,
            'reviews_count' => $this->reviews_count,
            'account_type' => $this->whenLoaded('accountType', fn () => ['id' => $this->accountType->id, 'name' => $this->accountType->name]),
            'sponsor_email' => $this->whenLoaded('sponsor', fn () => $this->sponsor?->email),
            'created_at' => $this->created_at,
        ];
    }
}
