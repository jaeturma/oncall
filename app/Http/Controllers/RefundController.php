<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRefundRequestRequest;
use App\Models\JobPayment;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;

class RefundController extends Controller
{
    public function store(StoreRefundRequestRequest $request, JobPayment $jobPayment, RefundService $refunds): RedirectResponse
    {
        $refunds->request(
            $jobPayment,
            $request->user(),
            (string) $request->input('amount'),
            $request->string('reason')->trim()->value(),
        );

        return redirect()->route('jobs.show', $jobPayment->job_id)->with('status', 'Refund request submitted for review.');
    }
}
