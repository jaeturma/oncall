<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmJobPaymentRequest;
use App\Http\Resources\Api\V1\JobPaymentResource;
use App\Models\JobPayment;
use App\Services\JobPaymentService;
use Illuminate\Http\JsonResponse;

class JobPaymentController extends Controller
{
    public function confirm(ConfirmJobPaymentRequest $request, JobPayment $jobPayment, JobPaymentService $payments): JsonResponse
    {
        $payments->confirmPaid(
            $jobPayment,
            $request->user(),
            $request->string('payment_method')->trim()->value(),
            $request->string('payment_reference')->trim()->value(),
        );

        return response()->json(['data' => new JobPaymentResource($jobPayment->fresh())]);
    }
}
