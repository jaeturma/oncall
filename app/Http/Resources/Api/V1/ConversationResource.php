<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** One row of the messaging inbox: a job with at least one message, mirroring `MessagesController`/`messages.index`. */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $otherParticipant = $this->otherParticipant($request->user());

        return [
            'job_id' => $this->id,
            'service' => $this->serviceRequest?->service ? ['id' => $this->serviceRequest->service->id, 'name' => $this->serviceRequest->service->name] : null,
            'with' => ['id' => $otherParticipant->id, 'name' => $otherParticipant->name],
            'unread_count' => $this->unread_count,
            'latest_message' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage ? [
                'body' => $this->latestMessage->body,
                'sender_id' => $this->latestMessage->sender_id,
                'created_at' => $this->latestMessage->created_at,
            ] : null),
        ];
    }
}
