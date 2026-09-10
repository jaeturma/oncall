<?php

namespace App\Http\Controllers;

use App\Services\WalletLedger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request, WalletLedger $ledger): View
    {
        $user = $request->user();

        return view('wallet.index', [
            'availableBalance' => $ledger->availableBalance($user),
            'pendingBalance' => $ledger->pendingBalance($user),
            'transactions' => $user->walletTransactions()->latest('id')->paginate(25),
        ]);
    }
}
