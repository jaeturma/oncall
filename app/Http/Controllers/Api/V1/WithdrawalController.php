<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWithdrawalRequest;
use App\Http\Resources\Api\V1\WithdrawalResource;
use App\Models\Withdrawal;
use App\Services\WalletLedger;
use App\Services\WithdrawalWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WithdrawalController extends Controller
{
    public function index(Request $request, WalletLedger $ledger): JsonResponse
    {
        $user = $request->user();
        $withdrawals = $user->withdrawals()->latest('id')->paginate(15);

        return response()->json([
            'available_balance' => $ledger->availableBalance($user),
            'can_request' => Gate::allows('create', Withdrawal::class),
            'data' => WithdrawalResource::collection($withdrawals),
            'meta' => ['current_page' => $withdrawals->currentPage(), 'last_page' => $withdrawals->lastPage(), 'total' => $withdrawals->total()],
        ]);
    }

    public function store(StoreWithdrawalRequest $request, WithdrawalWorkflow $workflow): JsonResponse
    {
        $withdrawal = $workflow->request(
            $request->user(),
            (string) $request->input('amount'),
            $request->string('payout_method')->trim()->value(),
            $request->string('payout_reference')->trim()->value(),
        );

        return response()->json(['data' => new WithdrawalResource($withdrawal)], 201);
    }

    public function cancel(Request $request, Withdrawal $withdrawal, WithdrawalWorkflow $workflow): WithdrawalResource
    {
        Gate::authorize('cancel', $withdrawal);
        $workflow->cancel($withdrawal, $request->user());

        return new WithdrawalResource($withdrawal->fresh());
    }
}
