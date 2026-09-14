<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Deliberately reshapes `data` rather than passing it through — a
     * notification's raw payload must never reach Flutter verbatim (Phase M
     * Step 3): only a `title`/`body` string pair and a whitelisted `target`
     * (validated server-side when the notification was created — see
     * NotificationCatalog::resolveTarget()) ever leave this resource.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->data['key'] ?? class_basename($this->type),
            'title' => $this->data['title'] ?? null,
            'body' => $this->data['body'] ?? null,
            'target' => $this->data['target'] ?? null,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
