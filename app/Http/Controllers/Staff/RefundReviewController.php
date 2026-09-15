<?php

namespace App\Http\Controllers\Staff;

use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecideRefundRequest;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RefundReviewController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewQueue', Refund::class);

        return view('staff.refunds.index', [
            'pending' => Refund::query()
                ->with(['jobPayment.provider:id,name', 'requester:id,name'])
                ->whereIn('status', [RefundStatus::Requested, RefundStatus::UnderReview])
                ->oldest('id')
                ->get(),
            'recent' => Refund::query()
                ->with('requester:id,name')
                ->whereIn('status', [RefundStatus::Completed, RefundStatus::Rejected])
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function update(DecideRefundRequest $request, Refund $refund, RefundService $refunds): RedirectResponse
    {
        if ($request->input('decision') === 'approve') {
            $refunds->approve($refund, $request->user(), $request->string('notes')->trim()->value() ?: null);
            $message = 'Refund approved and posted to the ledger.';
        } else {
            $refunds->reject($refund, $request->user(), $request->string('notes')->trim()->value());
            $message = 'Refund request rejected.';
        }

        return back()->with('status', $message);
    }
}
