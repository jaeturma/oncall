<?php

use App\Http\Controllers\Admin\AccountTypeController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CommissionController as AdminCommissionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DisputeController as AdminDisputeController;
use App\Http\Controllers\Admin\EnforcementCaseController as AdminEnforcementCaseController;
use App\Http\Controllers\Admin\FinanceReportController;
use App\Http\Controllers\Admin\JobController as AdminJobController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\ProviderController as AdminProviderController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ResolvedEnforcementCaseController;
use App\Http\Controllers\Admin\ReviewedEnforcementAppealController;
use App\Http\Controllers\Admin\ServiceCatalogController;
use App\Http\Controllers\Admin\SmsLogController;
use App\Http\Controllers\Admin\SmsSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VerificationReviewController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SendEmailVerificationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CancelledServiceRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\EnforcementAppealController;
use App\Http\Controllers\EnforcementCaseController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobMessageController;
use App\Http\Controllers\JobPaymentController;
use App\Http\Controllers\JobStatusController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\MobileVerificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Provider\AcceptedServiceRequestController;
use App\Http\Controllers\Provider\AvailabilityController;
use App\Http\Controllers\Provider\DeclinedServiceRequestController;
use App\Http\Controllers\Provider\ProfileController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ProviderSearchController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\SponsorController;
use App\Http\Controllers\Staff\JobPaymentReleaseController;
use App\Http\Controllers\Staff\WithdrawalReviewController;
use App\Http\Controllers\UserReportController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/find-help', ProviderSearchController::class)->name('providers.search');
Route::get('/providers/{provider_profile}', ProviderController::class)->name('providers.show');
Route::get('/locations/{province}/municipalities', [LocationController::class, 'municipalities'])->name('locations.municipalities');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email')->middleware('throttle:6,1');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update')->middleware('throttle:6,1');
});

Route::middleware(['auth', 'account.active'])->group(function () {
    // Shared / marketplace routes (customer, provider; sponsor is a
    // relationship on these same roles, not a separate route surface).
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/messages', [MessagesController::class, 'index'])->name('messages.index');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/provider/dashboard', [ProfileController::class, 'index'])->name('provider.dashboard');
    Route::resource('/provider/profiles', ProfileController::class)->only(['create', 'store', 'edit', 'update'])->parameters(['profiles' => 'provider_profile'])->names('provider.profiles');
    Route::patch('/provider/profiles/{provider_profile}/availability', AvailabilityController::class)->name('provider.availability.update');
    Route::get('/verification', [VerificationController::class, 'index'])->name('verification.index');
    Route::post('/verification/documents', [VerificationController::class, 'store'])->middleware('throttle:5,1')->name('verification.documents.store');
    Route::get('/verification/documents/{provider_document}', [VerificationController::class, 'download'])->name('verification.documents.download');
    Route::get('/verification/email/verify/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/verification/email/resend', SendEmailVerificationController::class)->middleware('throttle:6,1')->name('verification.send');
    Route::post('/verification/mobile', [MobileVerificationController::class, 'send'])->middleware('throttle:5,1')->name('verification.mobile.send');
    Route::post('/verification/mobile/confirm', [MobileVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('verification.mobile.verify');
    Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('service-requests.index');
    Route::get('/providers/{provider_profile}/service-requests/create', [ServiceRequestController::class, 'create'])->name('service-requests.create');
    Route::post('/providers/{provider_profile}/service-requests', [ServiceRequestController::class, 'store'])->middleware('throttle:10,1')->name('service-requests.store');
    Route::get('/service-requests/{service_request}', [ServiceRequestController::class, 'show'])->name('service-requests.show');
    Route::patch('/service-requests/{service_request}/accept', AcceptedServiceRequestController::class)->name('service-requests.accept');
    Route::patch('/service-requests/{service_request}/decline', DeclinedServiceRequestController::class)->name('service-requests.decline');
    Route::patch('/service-requests/{service_request}/cancel', CancelledServiceRequestController::class)->name('service-requests.cancel');
    Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show');
    Route::patch('/jobs/{job}/status', JobStatusController::class)->name('jobs.status.update');
    Route::post('/jobs/{job}/messages', [JobMessageController::class, 'store'])->middleware('throttle:20,1')->name('jobs.messages.store');
    Route::post('/jobs/{job}/reports', [UserReportController::class, 'store'])->middleware('throttle:5,1')->name('jobs.reports.store');
    Route::post('/jobs/{job}/reviews', [ReviewController::class, 'store'])->middleware('throttle:5,1')->name('jobs.reviews.store');
    Route::post('/jobs/{job}/disputes', [DisputeController::class, 'store'])->middleware('throttle:5,1')->name('disputes.store');
    Route::patch('/disputes/{dispute}/withdraw', [DisputeController::class, 'withdraw'])->name('disputes.withdraw');
    Route::patch('/job-payments/{job_payment}/confirm', [JobPaymentController::class, 'confirm'])->middleware('throttle:10,1')->name('job-payments.confirm');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('/wallet/withdrawals', [WithdrawalController::class, 'store'])->middleware('throttle:6,1')->name('withdrawals.store');
    Route::patch('/wallet/withdrawals/{withdrawal}/cancel', [WithdrawalController::class, 'cancel'])->name('withdrawals.cancel');
    Route::get('/sponsor/referrals', [SponsorController::class, 'index'])->name('sponsor.referrals');
    Route::get('/enforcement-cases', [EnforcementCaseController::class, 'index'])->name('enforcement-cases.index');
    Route::get('/enforcement-cases/{enforcement_case}', [EnforcementCaseController::class, 'show'])->name('enforcement-cases.show');
    Route::post('/enforcement-cases/{enforcement_case}/appeal', EnforcementAppealController::class)->name('enforcement-cases.appeal');

    // Web administration portal (Admin role only). `can:access-admin` is a
    // route-group-level backstop on top of each action's own authorization
    // (Gate::authorize / FormRequest::authorize / abort_unless) — it does not
    // replace those, it exists so a future /admin route added without its
    // own check still isn't reachable by a non-admin account.
    Route::prefix('admin')->name('admin.')->middleware('can:access-admin')->group(function () {
        Route::get('/catalog', [ServiceCatalogController::class, 'index'])->name('catalog');
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/providers', [AdminProviderController::class, 'index'])->name('providers.index');
        Route::get('/jobs', [AdminJobController::class, 'index'])->name('jobs.index');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::post('/categories', [ServiceCatalogController::class, 'storeCategory'])->name('categories.store');
        Route::post('/services', [ServiceCatalogController::class, 'storeService'])->name('services.store');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::get('/verifications', [VerificationReviewController::class, 'index'])->name('verifications.index');
        Route::patch('/verifications/{provider_document}', [VerificationReviewController::class, 'update'])->name('verifications.update');
        Route::get('/disputes', [AdminDisputeController::class, 'index'])->name('disputes.index');
        Route::get('/disputes/{dispute}', [AdminDisputeController::class, 'show'])->name('disputes.show');
        Route::patch('/disputes/{dispute}', [AdminDisputeController::class, 'update'])->name('disputes.update');
        Route::resource('/account-types', AccountTypeController::class)->except(['show', 'destroy'])->parameters(['account-types' => 'account_type'])->names('account-types');
        Route::patch('/account-types/{account_type}/toggle', [AccountTypeController::class, 'toggle'])->name('account-types.toggle');
        Route::get('/enforcement', [AdminEnforcementCaseController::class, 'index'])->name('enforcement.index');
        Route::get('/enforcement/{enforcement_case}', [AdminEnforcementCaseController::class, 'show'])->name('enforcement.show');
        Route::patch('/enforcement/{enforcement_case}', [AdminEnforcementCaseController::class, 'update'])->name('enforcement.update');
        Route::patch('/enforcement/{enforcement_case}/resolve', ResolvedEnforcementCaseController::class)->name('enforcement.resolve');
        Route::patch('/enforcement/{enforcement_case}/appeal', ReviewedEnforcementAppealController::class)->name('enforcement.appeal.update');

        // Phase L: SMS/OTP administration. Web-only by construction — there
        // is no equivalent route anywhere in routes/api.php, so this is
        // unreachable from a mobile Sanctum token regardless of role.
        Route::get('/settings/sms', [SmsSettingController::class, 'edit'])->name('settings.sms.edit');
        Route::patch('/settings/sms', [SmsSettingController::class, 'update'])->name('settings.sms.update');
        Route::post('/settings/sms/test', [SmsSettingController::class, 'test'])->middleware('throttle:5,1')->name('settings.sms.test');
        Route::get('/sms/logs', [SmsLogController::class, 'index'])->name('sms-logs.index');
    });

    // Commissions and finance reports are the one part of the admin area
    // Accounting can also reach (CommissionPolicy::REVIEWERS / the
    // `view-finance-reports` gate both allow Admin + Accounting) — a
    // sibling group under the same /admin prefix, gated with that existing,
    // already-correct permission instead of the Admin-only one above.
    Route::prefix('admin')->name('admin.')->middleware('can:view-finance-reports')->group(function () {
        Route::get('/commissions', [AdminCommissionController::class, 'index'])->name('commissions.index');
        Route::patch('/commissions/{commission}', [AdminCommissionController::class, 'update'])->name('commissions.update');
        Route::get('/finance', [FinanceReportController::class, 'index'])->name('finance.index');
        Route::get('/finance/ledger', [FinanceReportController::class, 'ledger'])->name('finance.ledger');
        Route::get('/finance/export', [FinanceReportController::class, 'export'])->name('finance.export');
        Route::get('/finance/users/{user}/statement', [FinanceReportController::class, 'userStatement'])->name('finance.statement');
    });

    // Back-office finance queues (Admin, Accounting, Budget, Cashier). Same
    // backstop principle as above, using the broader back-office gate since
    // these queues are shared across all four staff roles; each route's own
    // policy/FormRequest still enforces exactly which staff role may act on
    // a given step.
    Route::prefix('staff')->name('staff.')->middleware('can:access-back-office')->group(function () {
        Route::get('/job-payments', [JobPaymentReleaseController::class, 'index'])->name('job-payments.index');
        Route::patch('/job-payments/{job_payment}', [JobPaymentReleaseController::class, 'update'])->name('job-payments.update');
        Route::get('/withdrawals', [WithdrawalReviewController::class, 'index'])->name('withdrawals.index');
        Route::patch('/withdrawals/{withdrawal}', [WithdrawalReviewController::class, 'update'])->name('withdrawals.update');
    });
});

Route::get('/health', HealthController::class)->name('health');
