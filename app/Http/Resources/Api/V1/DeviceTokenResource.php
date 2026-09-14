<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Never includes the raw `fcm_token` — Flutter already has its own copy. */
class DeviceTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'device_name' => $this->device_name,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
