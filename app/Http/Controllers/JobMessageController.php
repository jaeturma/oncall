<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobMessageRequest;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;

class JobMessageController extends Controller
{
    public function store(StoreJobMessageRequest $request, Job $job): RedirectResponse
    {
        $job->messages()->create([
            'sender_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return back()->with('status', 'Message recorded in the booking history.');
    }
}
