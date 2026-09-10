<?php

namespace App\Http\Controllers\Staff;

use App\Enums\JobPaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReleaseJobPaymentRequest;
use App\Models\JobPayment;
use App\Services\JobPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class JobPaymentReleaseController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewQueue', JobPayment::class);

        return view('staff.job-payments.index', [
            'awaitingRelease' => JobPayment::query()
                ->with(['provider:id,name', 'job.serviceRequest:id,title'])
                ->where('status', JobPaymentStatus::Paid)
                ->oldest('confirmed_at')
                ->get(),
            'recent' => JobPayment::query()
                ->with('provider:id,name')
                ->whereIn('status', [JobPaymentStatus::Released, JobPaymentStatus::Reversed])
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function update(ReleaseJobPaymentRequest $request, JobPayment $jobPayment, JobPaymentService $payments): RedirectResponse
    {
        if ($request->input('decision') === 'release') {
            $payments->release($jobPayment, $request->user());
            $message = 'Job earning released to the provider wallet.';
        } else {
            $payments->reverse($jobPayment, $request->user(), $request->string('reason')->trim()->value());
            $message = 'Job earning reversed.';
        }

        return back()->with('status', $message);
    }
}
