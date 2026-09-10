<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWithdrawalRequest;
use App\Models\Withdrawal;
use App\Services\WalletLedger;
use App\Services\WithdrawalWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function index(Request $request, WalletLedger $ledger): View
    {
        $user = $request->user();

        return view('withdrawals.index', [
            'availableBalance' => $ledger->availableBalance($user),
            'canRequest' => Gate::allows('create', Withdrawal::class),
            'withdrawals' => $user->withdrawals()->latest('id')->paginate(15),
        ]);
    }

    public function store(StoreWithdrawalRequest $request, WithdrawalWorkflow $workflow): RedirectResponse
    {
        $workflow->request(
            $request->user(),
            (string) $request->input('amount'),
            $request->string('payout_method')->trim()->value(),
            $request->string('payout_reference')->trim()->value(),
        );

        return redirect()->route('withdrawals.index')->with('status', 'Withdrawal request submitted. The amount is now reserved.');
    }

    public function cancel(Request $request, Withdrawal $withdrawal, WithdrawalWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('cancel', $withdrawal);
        $workflow->cancel($withdrawal, $request->user());

        return back()->with('status', 'Withdrawal cancelled and the reserved amount returned to your wallet.');
    }
}
