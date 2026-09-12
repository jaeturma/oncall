<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobMessageRequest;
use App\Http\Resources\Api\V1\JobMessageResource;
use App\Models\Job;
use Illuminate\Http\JsonResponse;

class JobMessageController extends Controller
{
    public function store(StoreJobMessageRequest $request, Job $job): JsonResponse
    {
        $message = $job->messages()->create([
            'sender_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return response()->json(['data' => new JobMessageResource($message->load('sender:id,name'))], 201);
    }
}
