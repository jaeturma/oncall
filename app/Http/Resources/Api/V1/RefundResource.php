<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** decision_notes is internal-only (back-office review context) — never shown to the customer who requested the refund. */
class RefundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_payment_id' => $this->job_payment_id,
            'amount' => (string) $this->amount,
            'status' => $this->status->value,
            'reason' => $this->reason,
            'decision_notes' => $this->when($request->user()?->canAccessBackOffice() ?? false, $this->decision_notes),
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
        ];
    }
}
