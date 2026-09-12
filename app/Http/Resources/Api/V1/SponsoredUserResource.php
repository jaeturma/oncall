<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * A single row of "who I sponsored", mirroring `sponsor/referrals.blade.php`:
 * the sponsored user's name/account type/join date/identity status plus
 * their commission, if any has been earned yet. Never their email or phone.
 */
class SponsoredUserResource extends JsonResource
{
    public function __construct(private readonly $user, private readonly Collection $commissionByUserId)
    {
        parent::__construct($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $commission = $this->commissionByUserId->get($this->user->id);

        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'account_type' => $this->user->accountType?->name,
            'identity_verification_status' => $this->user->identity_verification_status?->value,
            'joined_at' => $this->user->created_at,
            'commission' => $commission ? ['amount' => $commission->amount, 'status' => $commission->status->value] : null,
        ];
    }
}
