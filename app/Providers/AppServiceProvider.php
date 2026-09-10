<?php

namespace App\Providers;

use App\Models\AccountType;
use App\Models\Commission;
use App\Models\EnforcementCase;
use App\Models\Job;
use App\Models\JobMessage;
use App\Models\JobPayment;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\UserReport;
use App\Models\Withdrawal;
use App\Policies\AccountTypePolicy;
use App\Policies\CommissionPolicy;
use App\Policies\EnforcementCasePolicy;
use App\Policies\JobMessagePolicy;
use App\Policies\JobPaymentPolicy;
use App\Policies\JobPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ServiceRequestPolicy;
use App\Policies\UserReportPolicy;
use App\Policies\WithdrawalPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
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
    }
}
