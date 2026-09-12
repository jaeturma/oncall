<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\AccountType;
use App\Models\Commission;
use App\Models\Dispute;
use App\Models\EnforcementCase;
use App\Models\Job;
use App\Models\JobMessage;
use App\Models\JobPayment;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\UserReport;
use App\Models\Withdrawal;
use App\Policies\AccountTypePolicy;
use App\Policies\CommissionPolicy;
use App\Policies\DisputePolicy;
use App\Policies\EnforcementCasePolicy;
use App\Policies\JobMessagePolicy;
use App\Policies\JobPaymentPolicy;
use App\Policies\JobPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ServiceRequestPolicy;
use App\Policies\UserReportPolicy;
use App\Policies\WithdrawalPolicy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::anonymousComponentPath(resource_path('views'));
        Gate::policy(ServiceRequest::class, ServiceRequestPolicy::class);
        Gate::policy(Job::class, JobPolicy::class);
        Gate::policy(JobMessage::class, JobMessagePolicy::class);
        Gate::policy(UserReport::class, UserReportPolicy::class);
        Gate::policy(EnforcementCase::class, EnforcementCasePolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(AccountType::class, AccountTypePolicy::class);
        Gate::policy(Commission::class, CommissionPolicy::class);
        Gate::policy(Withdrawal::class, WithdrawalPolicy::class);
        Gate::policy(JobPayment::class, JobPaymentPolicy::class);
        Gate::policy(Dispute::class, DisputePolicy::class);

        Gate::define('view-finance-reports', fn (User $user): bool => in_array($user->role, [UserRole::Admin, UserRole::Accounting], true));

        // Central marketplace vs. back-office boundary (Phase B). Defined
        // against a nullable user so these are also safe to evaluate for a
        // guest (e.g. if ever checked before the `auth` middleware runs),
        // rather than only ever being reachable behind existing auth checks.
        Gate::define('use-marketplace', fn (?User $user): bool => $user?->canUseMarketplace() ?? false);
        Gate::define('use-mobile', fn (?User $user): bool => $user?->canUseMobile() ?? false);
        Gate::define('access-admin', fn (?User $user): bool => $user?->canAccessAdmin() ?? false);
        Gate::define('access-back-office', fn (?User $user): bool => $user?->canAccessBackOffice() ?? false);

        // Laravel's slim skeleton has no EventServiceProvider to auto-wire
        // this, so it's registered explicitly: User implements
        // MustVerifyEmail, and this sends the verification email whenever a
        // Registered event fires (registration, and nowhere else).
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        // Phase D: throttles every /api/v1 route (via `$middleware->throttleApi()`
        // in bootstrap/app.php). Keyed by user when authenticated so one mobile
        // account can't exhaust another's quota; falls back to IP for the
        // unauthenticated login/register endpoints.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }
}
