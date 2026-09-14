<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobMessageRequest;
use App\Http\Resources\Api\V1\JobMessageResource;
use App\Models\Job;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\JsonResponse;

class JobMessageController extends Controller
{
    public function store(StoreJobMessageRequest $request, Job $job, NotificationDispatcher $notifications): JsonResponse
    {
        $sender = $request->user();
        $message = $job->messages()->create([
            'sender_id' => $sender->id,
            ...$request->validated(),
        ]);

        $recipientId = $sender->id === $job->service_finder_id ? $job->provider_id : $job->service_finder_id;
        $notifications->dispatch(
            User::find($recipientId),
            'new_message',
            ['sender_name' => $sender->name],
            ['screen' => 'conversation', 'id' => $job->id],
        );

        return response()->json(['data' => new JobMessageResource($message->load('sender:id,name'))], 201);
    }
}
