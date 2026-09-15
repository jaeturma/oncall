<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmJobPaymentRequest;
use App\Models\JobPayment;
use App\Services\JobPaymentService;
use App\Services\ReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class JobPaymentController extends Controller
{
    public function confirm(ConfirmJobPaymentRequest $request, JobPayment $jobPayment, JobPaymentService $payments): RedirectResponse
    {
        $payments->confirmPaid(
            $jobPayment,
            $request->user(),
            $request->string('payment_method')->trim()->value(),
            $request->string('payment_reference')->trim()->value(),
        );

        return back()->with('status', 'Payment recorded. The provider earning is pending Oncall release.');
    }

    public function receipt(JobPayment $jobPayment, ReceiptService $receipts): View
    {
        Gate::authorize('viewReceipt', $jobPayment);

        return view('payments.receipt', ['receipt' => $receipts->forPayment($jobPayment)]);
    }
}
