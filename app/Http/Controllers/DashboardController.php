<?php

namespace App\Http\Controllers;

use App\Enums\CommissionStatus;
use App\Enums\JobPaymentStatus;
use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Enums\WithdrawalStatus;
use App\Models\JobPayment;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\WalletLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Role-aware account overview. Every number here is a real query; nothing
 * is decorative. Providers and admins are sent to their dedicated overviews.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, WalletLedger $ledger): View|RedirectResponse
    {
        $user = $request->user();

        return match ($user->role) {
            UserRole::ServiceProvider => redirect()->route('provider.dashboard'),
            UserRole::Admin => redirect()->route('admin.dashboard'),
            UserRole::Accounting, UserRole::Budget, UserRole::Cashier => $this->staffDashboard($request),
            default => $this->finderDashboard($request, $ledger),
        };
    }

    private function finderDashboard(Request $request, WalletLedger $ledger): View
    {
        $user = $request->user();
        $activeStatuses = [ServiceRequestStatus::Requested, ServiceRequestStatus::Searching];
        $activeJobStatuses = [JobStatus::Accepted, JobStatus::OnTheWay, JobStatus::InProgress];

        $sponsoredCount = $user->sponsoredUsers()->count();
        $commissions = $sponsoredCount > 0 ? $user->commissionsEarned()->get(['amount', 'status']) : collect();

        return view('dashboard', [
            'metrics' => [
                'pending_requests' => $user->serviceRequests()->whereIn('status', $activeStatuses)->count(),
                'active_jobs' => $user->finderJobs()->whereIn('status', $activeJobStatuses)->count(),
                'completed_jobs' => $user->finderJobs()->where('status', JobStatus::Completed)->count(),
                'unread_notifications' => $user->unreadNotifications()->count(),
            ],
            'recentRequests' => $user->serviceRequests()->with(['service:id,name', 'municipality:id,name', 'province:id,name'])->latest()->limit(5)->get(),
            'recentJobs' => $user->finderJobs()->with(['serviceRequest.service:id,name'])->latest('id')->limit(5)->get(),
            'onboarding' => $this->finderOnboardingItems($user),
            'walletAvailable' => $ledger->availableBalance($user),
            'sponsor' => $sponsoredCount > 0 ? [
                'count' => $sponsoredCount,
                'verified' => $user->sponsoredUsers()->whereHas('providerDocuments', fn ($query) => $query->where('status', VerificationStatus::Verified))->count(),
                'pending' => $commissions->whereIn('status', [CommissionStatus::Pending, CommissionStatus::Approved])->sum('amount'),
                'earned' => $commissions->where('status', CommissionStatus::Available)->sum('amount'),
            ] : null,
        ]);
    }

    /**
     * @return list<array{label: string, description: string, done: bool, href: string, cta: string, audience: string}>
     */
    private function finderOnboardingItems(User $user): array
    {
        return [
            ['label' => 'Verify your email', 'description' => 'Confirm the link Oncall sent to '.$user->email.'.', 'done' => $user->hasVerifiedEmail(), 'href' => route('verification.index'), 'cta' => 'Verify email', 'audience' => 'request from'],
            ['label' => 'Verify your mobile number', 'description' => 'A quick code confirms it\'s really you.', 'done' => $user->isMobileVerified(), 'href' => route('verification.index'), 'cta' => 'Verify mobile', 'audience' => 'request from'],
            ['label' => 'Verify your identity', 'description' => 'Required before you can send a service request.', 'done' => $user->isIdentityVerified(), 'href' => route('verification.index'), 'cta' => 'Verify identity', 'audience' => 'request from'],
        ];
    }

    private function staffDashboard(Request $request): View
    {
        $role = $request->user()->role;
        $myStep = collect(WithdrawalStatus::openStates())->filter(fn (WithdrawalStatus $status): bool => $status->actingRole() === $role)->all();

        return view('dashboard-staff', [
            'role' => $role,
            'metrics' => [
                'withdrawals_for_me' => Withdrawal::query()->whereIn('status', $myStep)->count(),
                'withdrawals_open' => Withdrawal::query()->whereIn('status', WithdrawalStatus::openStates())->count(),
                'payments_awaiting_release' => JobPayment::query()->where('status', JobPaymentStatus::Paid)->count(),
            ],
            'queue' => Withdrawal::query()->with('user:id,name')->whereIn('status', $myStep)->oldest('id')->limit(5)->get(),
        ]);
    }
}
