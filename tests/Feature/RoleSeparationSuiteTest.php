<?php

namespace Tests\Feature;

use App\Enums\JobPaymentStatus;
use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Models\EnforcementCase;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\Municipality;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\WalletLedger;
use App\Services\WithdrawalWorkflow;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase J — a single canonical suite proving the 20 required web/mobile role
 * separation scenarios via actual routes/middleware, not just unit helpers.
 * Most of these are also covered incidentally elsewhere (RoleBoundaryTest,
 * WithdrawalWorkflowTest, MobileApiAuthTest, SafetyEnforcementTest, ...) —
 * this file exists so the full list has one place to point to and one place
 * that fails when any of it regresses.
 */
class RoleSeparationSuiteTest extends TestCase
{
    use LazilyRefreshDatabase;

    // 1. Customer can log into mobile API.
    public function test_01_customer_can_log_into_mobile_api(): void
    {
        $customer = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active, 'password' => 'password123']);

        $this->postJson('/api/v1/auth/login', ['email' => $customer->email, 'password' => 'password123'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'role'], 'token']);
    }

    // 2. Provider can log into mobile API.
    public function test_02_provider_can_log_into_mobile_api(): void
    {
        $provider = User::factory()->serviceProvider()->create(['status' => UserStatus::Active, 'password' => 'password123']);

        $this->postJson('/api/v1/auth/login', ['email' => $provider->email, 'password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('data.role', 'SERVICE_PROVIDER');
    }

    // 3. Sponsor can use allowed sponsor features.
    public function test_03_sponsor_can_use_allowed_sponsor_features(): void
    {
        $sponsor = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        $sponsored = User::factory()->create(['sponsor_user_id' => $sponsor->id]);
        Sanctum::actingAs($sponsor);

        $this->getJson('/api/v1/sponsor/referrals')
            ->assertOk()
            ->assertJsonFragment(['name' => $sponsored->name]);
    }

    // 4-7. Back-office-only roles cannot use mobile login.
    public function test_04_07_back_office_only_roles_cannot_use_mobile_login(): void
    {
        foreach ([UserRole::Admin, UserRole::Accounting, UserRole::Budget, UserRole::Cashier] as $role) {
            $staff = User::factory()->create(['role' => $role, 'status' => UserStatus::Active, 'password' => 'password123']);

            $this->postJson('/api/v1/auth/login', ['email' => $staff->email, 'password' => 'password123'])
                ->assertUnprocessable();
            $this->assertSame(0, $staff->tokens()->count(), "{$role->value} must not receive a mobile token");
        }
    }

    // 8-9. Customer/provider cannot access /admin.
    public function test_08_09_customer_and_provider_cannot_access_admin(): void
    {
        $customer = User::factory()->create(['role' => UserRole::ServiceFinder]);
        $provider = User::factory()->serviceProvider()->create();

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($provider)->get(route('admin.dashboard'))->assertForbidden();
    }

    // 10. Sponsor cannot access /admin.
    public function test_10_sponsor_cannot_access_admin(): void
    {
        $sponsor = User::factory()->create(['role' => UserRole::ServiceFinder]);
        User::factory()->create(['sponsor_user_id' => $sponsor->id]);

        $this->actingAs($sponsor)->get(route('admin.dashboard'))->assertForbidden();
    }

    // 11. Accounting cannot perform budget approval.
    public function test_11_accounting_cannot_perform_budget_approval(): void
    {
        $withdrawal = $this->withdrawalAt(WithdrawalStatus::BudgetApproval);
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);

        $this->actingAs($accounting)
            ->patch(route('staff.withdrawals.update', $withdrawal), ['decision' => 'approve'])
            ->assertForbidden();
        $this->assertSame(WithdrawalStatus::BudgetApproval, $withdrawal->fresh()->status);
    }

    // 12. Budget cannot perform cashier disbursement.
    public function test_12_budget_cannot_perform_cashier_disbursement(): void
    {
        $withdrawal = $this->withdrawalAt(WithdrawalStatus::ForDisbursement);
        $budget = User::factory()->create(['role' => UserRole::Budget]);

        $this->actingAs($budget)
            ->patch(route('staff.withdrawals.update', $withdrawal), ['decision' => 'approve'])
            ->assertForbidden();
        $this->assertSame(WithdrawalStatus::ForDisbursement, $withdrawal->fresh()->status);
    }

    // 13. Cashier cannot perform accounting approval.
    public function test_13_cashier_cannot_perform_accounting_approval(): void
    {
        $withdrawal = $this->withdrawalAt(WithdrawalStatus::AccountingReview);
        $cashier = User::factory()->create(['role' => UserRole::Cashier]);

        $this->actingAs($cashier)
            ->patch(route('staff.withdrawals.update', $withdrawal), ['decision' => 'approve'])
            ->assertForbidden();
        $this->assertSame(WithdrawalStatus::AccountingReview, $withdrawal->fresh()->status);
    }

    // 14. Mobile API cannot approve cashout — no such route exists at all.
    public function test_14_mobile_api_cannot_approve_cashout(): void
    {
        $withdrawal = $this->withdrawalAt(WithdrawalStatus::AccountingReview);
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        Sanctum::actingAs($accounting);

        $this->patchJson("/api/v1/wallet/withdrawals/{$withdrawal->id}", ['decision' => 'approve'])->assertNotFound();
        $this->assertFalse(Route::has('api.withdrawals.update'));
        $this->assertSame(WithdrawalStatus::AccountingReview, $withdrawal->fresh()->status);
    }

    // 15. Mobile API cannot approve verification — no such route exists at all.
    public function test_15_mobile_api_cannot_approve_verification(): void
    {
        $document = ProviderDocument::factory()->create();
        // Admin cannot even obtain a mobile token (scenario 4), so this proves
        // the capability is structurally absent regardless of who is asking.
        $this->assertFalse(Route::has('api.verification.update'));

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]));
        $this->patchJson("/api/v1/verification/documents/{$document->id}", ['status' => 'VERIFIED'])->assertNotFound();
        $this->assertSame(VerificationStatus::Submitted, $document->fresh()->status);
    }

    // 16. Mobile API cannot suspend accounts — no such route exists at all.
    public function test_16_mobile_api_cannot_suspend_accounts(): void
    {
        $target = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        $case = EnforcementCase::factory()->create(['user_id' => $target->id]);
        $this->assertFalse(Route::has('api.enforcement-cases.update'));

        Sanctum::actingAs($target);
        // GET /api/v1/enforcement-cases/{id} exists (viewing your own case); PATCH does not — 405, not 404.
        $this->patchJson("/api/v1/enforcement-cases/{$case->id}", ['action' => 'SUSPENSION'])->assertMethodNotAllowed();
        $this->assertSame(UserStatus::Active, $target->fresh()->status);
    }

    // 17. Restricted user cannot perform blocked actions.
    public function test_17_restricted_user_cannot_perform_blocked_action(): void
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Restricted]);
        EnforcementCase::factory()->create([
            'user_id' => $finder->id,
            'status' => 'RESTRICTED',
            'restricted_capabilities' => ['NEW_BOOKINGS'],
            'ends_at' => now()->addDays(7),
        ]);
        $profile = ProviderProfile::factory()->create(['verification_status' => VerificationStatus::Verified]);

        $this->actingAs($finder)
            ->post(route('service-requests.store', $profile), ['service_id' => 1])
            ->assertForbidden();
    }

    // 18. Suspended user cannot perform blocked actions.
    public function test_18_suspended_user_cannot_perform_blocked_action(): void
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Suspended]);

        $this->actingAs($finder)->get(route('jobs.index'))->assertRedirect(route('enforcement-cases.index'));

        Sanctum::actingAs($finder);
        $this->getJson('/api/v1/jobs')->assertForbidden();
    }

    // 19. Guest cannot obtain private provider contact details.
    public function test_19_guest_cannot_obtain_private_provider_contact_details(): void
    {
        $provider = User::factory()->serviceProvider()->identityVerified()->create(['phone' => '09171234567']);
        ProviderDocument::factory()->create(['user_id' => $provider->id, 'status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id, 'verification_status' => VerificationStatus::Verified]);

        $this->get(route('providers.show', $profile))
            ->assertOk()
            ->assertDontSee($provider->name)
            ->assertDontSee($provider->email)
            ->assertDontSee($provider->phone);

        // Mobile has no guest browsing at all — every /api/v1 route requires a token.
        $this->getJson("/api/v1/providers/{$profile->id}")->assertUnauthorized();
    }

    // 20. API resources do not leak internal admin notes / staff actor ids.
    public function test_20_api_resources_do_not_leak_internal_admin_notes(): void
    {
        $province = Province::factory()->create();
        $municipality = Municipality::factory()->for($province)->create();
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        $provider = User::factory()->serviceProvider()->create(['status' => UserStatus::Active]);
        ProviderProfile::factory()->create(['user_id' => $provider->id, 'province_id' => $province->id, 'municipality_id' => $municipality->id]);
        $service = Service::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create([
            'service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id, 'service_id' => $service->id,
            'province_id' => $province->id, 'municipality_id' => $municipality->id, 'status' => ServiceRequestStatus::Accepted,
        ]);
        $job = Job::factory()->create(['service_request_id' => $serviceRequest->id, 'service_finder_id' => $finder->id, 'provider_id' => $provider->id, 'status' => JobStatus::Completed]);
        $staff = User::factory()->admin()->create();
        JobPayment::create([
            'job_id' => $job->id,
            'provider_id' => $provider->id,
            'gross_amount' => '1000.00',
            'platform_fee' => '100.00',
            'net_amount' => '900.00',
            'status' => JobPaymentStatus::Reversed,
            'notes' => 'Internal: reversed after chargeback investigation.',
            'confirmed_by' => $staff->id,
            'released_by' => $staff->id,
        ]);

        Sanctum::actingAs($finder);
        $jobResponse = $this->getJson("/api/v1/jobs/{$job->id}");
        $jobResponse->assertOk();
        $jobResponse->assertJsonMissingPath('data.payment.notes');
        $jobResponse->assertJsonMissingPath('data.payment.confirmed_by');
        $jobResponse->assertJsonMissingPath('data.payment.released_by');

        $withdrawal = $this->withdrawalAt(WithdrawalStatus::Completed, $finder);
        $withdrawalsResponse = $this->getJson('/api/v1/wallet/withdrawals');
        $withdrawalsResponse->assertOk();
        $withdrawalsResponse->assertJsonMissingPath('data.0.accounting_reviewed_by');
        $withdrawalsResponse->assertJsonMissingPath('data.0.budget_approved_by');
        $withdrawalsResponse->assertJsonMissingPath('data.0.disbursed_by');
        $withdrawalsResponse->assertJsonMissingPath('data.0.hold_transaction_id');

        $document = ProviderDocument::factory()->for($finder)->create(['status' => VerificationStatus::Rejected, 'reviewed_by' => $staff->id, 'private_path' => 'verification-documents/secret.pdf']);
        $verificationResponse = $this->getJson('/api/v1/verification');
        $verificationResponse->assertOk();
        $verificationResponse->assertJsonMissingPath('data.0.private_path');
        $verificationResponse->assertJsonMissingPath('data.0.reviewed_by');
        $this->assertNotNull($document->id);

        $case = EnforcementCase::factory()->create(['user_id' => $finder->id, 'handled_by' => $staff->id]);
        $caseResponse = $this->getJson("/api/v1/enforcement-cases/{$case->id}");
        $caseResponse->assertOk();
        $caseResponse->assertJsonMissingPath('data.handled_by');
    }

    private function withdrawalAt(WithdrawalStatus $status, ?User $owner = null): Withdrawal
    {
        $user = $owner ?? User::factory()->create(['status' => UserStatus::Active]);
        app(WalletLedger::class)->post($user, WalletTransactionType::Adjustment, '1000.00', description: 'test funding');
        $workflow = app(WithdrawalWorkflow::class);
        $withdrawal = $workflow->request($user, '150.00', 'GCash', 'ref');

        $roles = [UserRole::Accounting, UserRole::Budget, UserRole::Cashier];
        $stepIndex = 0;
        while ($withdrawal->status !== $status && $stepIndex < 3) {
            $withdrawal = $workflow->advance($withdrawal, User::factory()->create(['role' => $roles[$stepIndex]]), 'approve');
            $stepIndex++;
        }

        return $withdrawal;
    }
}
