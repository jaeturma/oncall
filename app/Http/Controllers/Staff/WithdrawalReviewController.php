<?php

namespace App\Http\Controllers\Staff;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewWithdrawalRequest;
use App\Models\Withdrawal;
use App\Services\WithdrawalWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WithdrawalReviewController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewQueue', Withdrawal::class);

        return view('staff.withdrawals.index', [
            'open' => Withdrawal::query()
                ->with('user:id,name,email')
                ->whereIn('status', WithdrawalStatus::openStates())
                ->oldest('id')
                ->get(),
            'recent' => Withdrawal::query()
                ->with('user:id,name')
                ->whereNotIn('status', WithdrawalStatus::openStates())
                ->latest('id')
                ->limit(20)
                ->get(),
            'actingRole' => $request->user()->role,
        ]);
    }

    public function update(ReviewWithdrawalRequest $request, Withdrawal $withdrawal, WithdrawalWorkflow $workflow): RedirectResponse
    {
        $workflow->advance(
            $withdrawal,
            $request->user(),
            $request->string('decision')->value(),
            $request->string('notes')->trim()->value() ?: null,
        );

        return back()->with('status', 'Withdrawal updated.');
    }
}
