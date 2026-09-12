<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WalletTransactionResource;
use App\Services\WalletLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request, WalletLedger $ledger): JsonResponse
    {
        $user = $request->user();
        $transactions = $user->walletTransactions()->latest('id')->paginate(25);

        return response()->json([
            'available_balance' => $ledger->availableBalance($user),
            'pending_balance' => $ledger->pendingBalance($user),
            'data' => WalletTransactionResource::collection($transactions),
            'meta' => ['current_page' => $transactions->currentPage(), 'last_page' => $transactions->lastPage(), 'total' => $transactions->total()],
        ]);
    }
}
