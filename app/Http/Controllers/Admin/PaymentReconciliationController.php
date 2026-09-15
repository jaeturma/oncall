<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReconciliationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveReconciliationFlagRequest;
use App\Models\ReconciliationFlag;
use App\Services\ReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentReconciliationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('view-payment-reconciliation');

        return view('admin.finance.reconciliation', [
            'flags' => ReconciliationFlag::query()
                ->with(['jobPayment', 'paymentAttempt'])
                ->where('status', ReconciliationStatus::Open)
                ->latest('id')
                ->paginate(25),
        ]);
    }

    public function resolve(ResolveReconciliationFlagRequest $request, ReconciliationFlag $reconciliationFlag, ReconciliationService $reconciliation): RedirectResponse
    {
        Gate::authorize('view-payment-reconciliation');

        $reconciliation->resolve($reconciliationFlag, $request->user(), $request->string('resolution_notes')->trim()->value());

        return back()->with('status', 'Reconciliation flag resolved.');
    }
}
