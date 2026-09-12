<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'body' => $this->body,
            'sender' => $this->whenLoaded('sender', fn () => ['id' => $this->sender->id, 'name' => $this->sender->name]),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
