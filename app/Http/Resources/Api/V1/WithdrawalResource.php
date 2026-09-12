<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A cashout request owned by the current user. `notes` is the staff decision note, shown here because the web withdrawals page already shows it to the owner. */
class WithdrawalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'status' => $this->status->value,
            'payout_method' => $this->payout_method,
            'payout_reference' => $this->payout_reference,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
