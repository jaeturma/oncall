<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\CancelledServiceRequestController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\DisputeController;
use App\Http\Controllers\Api\V1\EnforcementAppealController;
use App\Http\Controllers\Api\V1\EnforcementCaseController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\JobMessageController;
use App\Http\Controllers\Api\V1\JobPaymentController;
use App\Http\Controllers\Api\V1\JobReviewEligibilityController;
use App\Http\Controllers\Api\V1\JobStatusController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\MessagesController;
use App\Http\Controllers\Api\V1\MobileVerificationController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\Provider\AcceptedServiceRequestController;
use App\Http\Controllers\Api\V1\Provider\AvailabilityController;
use App\Http\Controllers\Api\V1\Provider\DeclinedServiceRequestController;
use App\Http\Controllers\Api\V1\Provider\ProfileController as ProviderProfileController;
use App\Http\Controllers\Api\V1\ProviderController;
use App\Http\Controllers\Api\V1\ProviderReviewController;
use App\Http\Controllers\Api\V1\ProviderSearchController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\ReviewHistoryController;
use App\Http\Controllers\Api\V1\ReviewReportController;
use App\Http\Controllers\Api\V1\ReviewResponseController;
use App\Http\Controllers\Api\V1\ServiceRequestController;
use App\Http\Controllers\Api\V1\SponsorController;
use App\Http\Controllers\Api\V1\UserReportController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\WithdrawalController;
use Illuminate\Support\Facades\Route;

/*
 * Phase D — marketplace-only mobile API (`/api/v1`). Every domain here maps
 * to an allowed domain from the phase spec: auth, profile, verification,
 * services/locations, provider search/profile, bookings, messaging,
 * notifications, wallet, cashout, sponsored users, incident reporting.
 * Forbidden back-office domains (user management, roles, account-type/fee/
 * commission config, wallet adjustments, verification/accounting/budget/
 * cashier approval, enforcement administration, settings, audit logs) have
 * no route here at all — they stay on the web-only `/admin` and `/staff`
 * surfaces from Phase B/C.
 */
Route::name('api.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1')->name('register');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1')->name('login');
    });

    // Everything below requires a marketplace-capable mobile token
    // (`auth:sanctum` + `can:use-mobile`), plus the same suspension check as
    // the web app (`account.active`). `can:use-mobile` is a defense-in-depth
    // backstop, mirroring the `can:access-admin` pattern from Phase B/C: the
    // login endpoint already refuses a token to a non-mobile-capable account,
    // but this catches a token that outlives a role change.
    Route::middleware(['auth:sanctum', 'account.active', 'can:use-mobile'])->group(function () {
        Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy'])->name('auth.logout');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');

        Route::get('/provider/profile', [ProviderProfileController::class, 'show'])->name('provider.profile.show');
        Route::post('/provider/profile', [ProviderProfileController::class, 'store'])->name('provider.profile.store');
        Route::patch('/provider/profiles/{provider_profile}', [ProviderProfileController::class, 'update'])->name('provider.profile.update');
        Route::patch('/provider/profiles/{provider_profile}/availability', AvailabilityController::class)->name('provider.availability.update');

        Route::get('/verification', [VerificationController::class, 'index'])->name('verification.index');
        Route::post('/verification/documents', [VerificationController::class, 'store'])->middleware('throttle:5,1')->name('verification.documents.store');

        // Phase L: OTP request/verify/resend. Never returns the code or SMS
        // provider config — see MobileVerificationController's docblock.
        Route::post('/mobile-verification/request', [MobileVerificationController::class, 'request'])->middleware('throttle:5,1')->name('mobile-verification.request');
        Route::post('/mobile-verification/verify', [MobileVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('mobile-verification.verify');
        Route::post('/mobile-verification/resend', [MobileVerificationController::class, 'resend'])->middleware('throttle:5,1')->name('mobile-verification.resend');

        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
        Route::get('/provinces', [LocationController::class, 'index'])->name('provinces.index');
        Route::get('/provinces/{province}/municipalities', [LocationController::class, 'municipalities'])->name('provinces.municipalities');
        Route::get('/locations/municipalities/{municipality}/barangays', [LocationController::class, 'barangays'])->name('locations.barangays');
        // Anti-scraping (Phase O §26): reverse-geocoding is a natural
        // triangulation/coordinate-scan target, so it gets its own tighter
        // throttle rather than sharing the general per-minute API limiter.
        Route::get('/locations/reverse-geocode', [LocationController::class, 'reverseGeocode'])->middleware('throttle:20,1')->name('locations.reverse-geocode');

        Route::get('/providers/search', ProviderSearchController::class)->middleware('throttle:30,1')->name('providers.search');
        Route::get('/providers/{provider_profile}', [ProviderController::class, 'show'])->name('providers.show');

        Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('service-requests.index');
        Route::post('/providers/{provider_profile}/service-requests', [ServiceRequestController::class, 'store'])->middleware('throttle:10,1')->name('service-requests.store');
        Route::get('/service-requests/{service_request}', [ServiceRequestController::class, 'show'])->name('service-requests.show');
        Route::patch('/service-requests/{service_request}/accept', AcceptedServiceRequestController::class)->name('service-requests.accept');
        Route::patch('/service-requests/{service_request}/decline', DeclinedServiceRequestController::class)->name('service-requests.decline');
        Route::patch('/service-requests/{service_request}/cancel', CancelledServiceRequestController::class)->name('service-requests.cancel');

        Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show');
        Route::patch('/jobs/{job}/status', JobStatusController::class)->name('jobs.status.update');
        Route::patch('/job-payments/{job_payment}/confirm', [JobPaymentController::class, 'confirm'])->middleware('throttle:10,1')->name('job-payments.confirm');
        Route::post('/jobs/{job}/messages', [JobMessageController::class, 'store'])->middleware('throttle:20,1')->name('jobs.messages.store');
        Route::post('/jobs/{job}/reviews', [ReviewController::class, 'store'])->middleware('throttle:5,1')->name('jobs.reviews.store');
        Route::get('/jobs/{job}/review-eligibility', JobReviewEligibilityController::class)->name('jobs.review-eligibility');
        Route::patch('/reviews/{review}/withdraw', [ReviewController::class, 'withdraw'])->name('reviews.withdraw');
        Route::post('/reviews/{review}/response', ReviewResponseController::class)->middleware('throttle:10,1')->name('reviews.response.store');
        Route::post('/reviews/{review}/reports', ReviewReportController::class)->middleware('throttle:10,1')->name('reviews.reports.store');
        Route::get('/reviews/written', [ReviewHistoryController::class, 'written'])->name('reviews.written');
        Route::get('/reviews/received', [ReviewHistoryController::class, 'received'])->name('reviews.received');
        Route::get('/providers/{provider_profile}/reviews', ProviderReviewController::class)->name('providers.reviews.index');
        Route::post('/jobs/{job}/reports', [UserReportController::class, 'store'])->middleware('throttle:5,1')->name('jobs.reports.store');
        Route::post('/jobs/{job}/disputes', [DisputeController::class, 'store'])->middleware('throttle:5,1')->name('disputes.store');
        Route::patch('/disputes/{dispute}/withdraw', [DisputeController::class, 'withdraw'])->name('disputes.withdraw');

        Route::get('/messages', [MessagesController::class, 'index'])->name('messages.index');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

        // Phase M: device-token registration for push. `register` also
        // serves as the token-refresh endpoint (Step 8). The recipient is
        // always the authenticated user — see RegisterDeviceRequest's
        // docblock for why there is no `user_id` field.
        Route::post('/devices/register', [DeviceController::class, 'register'])->middleware('throttle:20,1')->name('devices.register');
        Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');

        Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index'])->name('notification-preferences.index');
        Route::patch('/notification-preferences', [NotificationPreferenceController::class, 'update'])->middleware('throttle:20,1')->name('notification-preferences.update');

        Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
        Route::get('/wallet/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::post('/wallet/withdrawals', [WithdrawalController::class, 'store'])->middleware('throttle:6,1')->name('withdrawals.store');
        Route::patch('/wallet/withdrawals/{withdrawal}/cancel', [WithdrawalController::class, 'cancel'])->name('withdrawals.cancel');

        Route::get('/sponsor/referrals', [SponsorController::class, 'index'])->name('sponsor.referrals');

        Route::get('/enforcement-cases', [EnforcementCaseController::class, 'index'])->name('enforcement-cases.index');
        Route::get('/enforcement-cases/{enforcement_case}', [EnforcementCaseController::class, 'show'])->name('enforcement-cases.show');
        Route::post('/enforcement-cases/{enforcement_case}/appeal', EnforcementAppealController::class)->name('enforcement-cases.appeal');
    });
});
