<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Mirrors the "Payment" card on the web booking page: amounts and status only — never the staff `notes` field or `confirmed_by`/`released_by` actor ids. */
class JobPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'gross_amount' => $this->gross_amount,
            'platform_fee' => $this->platform_fee,
            'net_amount' => $this->net_amount,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'confirmed_at' => $this->confirmed_at,
            'released_at' => $this->released_at,
        ];
    }
}
