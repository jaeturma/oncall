<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobMessageRequest;
use App\Models\Job;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;

class JobMessageController extends Controller
{
    public function store(StoreJobMessageRequest $request, Job $job, NotificationDispatcher $notifications): RedirectResponse
    {
        $sender = $request->user();
        $job->messages()->create([
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

        return back()->with('status', 'Message recorded in the booking history.');
    }
}
