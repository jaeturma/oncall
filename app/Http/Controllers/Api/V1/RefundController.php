<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRefundRequestRequest;
use App\Http\Resources\Api\V1\RefundResource;
use App\Models\JobPayment;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RefundController extends Controller
{
    public function index(JobPayment $jobPayment): JsonResponse
    {
        Gate::authorize('viewReceipt', $jobPayment);

        return response()->json([
            'data' => RefundResource::collection($jobPayment->refunds()->latest('id')->get()),
        ]);
    }

    public function store(StoreRefundRequestRequest $request, JobPayment $jobPayment, RefundService $refunds): JsonResponse
    {
        $refund = $refunds->request(
            $jobPayment,
            $request->user(),
            (string) $request->input('amount'),
            $request->string('reason')->trim()->value(),
        );

        return response()->json(['data' => new RefundResource($refund)], 201);
    }
}
