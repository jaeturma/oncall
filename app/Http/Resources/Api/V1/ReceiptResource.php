<?php

namespace App\Http\Resources\Api\V1;

use App\Services\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Wraps the already-sanitized array {@see ReceiptService::forPayment()} builds. */
class ReceiptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
