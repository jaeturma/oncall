<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Phase B — marketplace vs. back-office role boundaries. Covers the three
 * central capability concepts (canUseMarketplace / canUseMobile /
 * canAccessAdmin, plus the back-office category canAccessBackOffice) at the
 * unit level, then proves the route-group backstop actually rejects the
 * wrong side of the boundary over HTTP.
 */
class RoleBoundaryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_is_marketplace_and_mobile_capable_but_not_admin_capable(): void
    {
        $customer = User::factory()->create(['role' => UserRole::ServiceFinder]);

        $this->assertTrue($customer->canUseMarketplace());
        $this->assertTrue($customer->canUseMobile());
        $this->assertFalse($customer->canAccessAdmin());
        $this->assertFalse($customer->canAccessBackOffice());
    }

    public function test_provider_is_marketplace_and_mobile_capable_but_not_admin_capable(): void
    {
        $provider = User::factory()->serviceProvider()->create();

        $this->assertTrue($provider->canUseMarketplace());
        $this->assertTrue($provider->canUseMobile());
        $this->assertFalse($provider->canAccessAdmin());
        $this->assertFalse($provider->canAccessBackOffice());
    }

    public function test_sponsor_is_marketplace_capable(): void
    {
        // Sponsor/Referrer is not a role (ADR-001) — it's a customer or
        // provider account that other users registered under.
        $sponsor = User::factory()->create(['role' => UserRole::ServiceFinder]);
        User::factory()->count(2)->create(['sponsor_user_id' => $sponsor->id]);

        $this->assertTrue($sponsor->sponsoredUsers()->exists());
        $this->assertTrue($sponsor->canUseMarketplace());
        $this->assertTrue($sponsor->canUseMobile());
        $this->assertFalse($sponsor->canAccessAdmin());
    }

    public function test_admin_is_not_mobile_capable_but_is_admin_and_back_office_capable(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->canUseMarketplace());
        $this->assertFalse($admin->canUseMobile());
        $this->assertTrue($admin->canAccessAdmin());
        $this->assertTrue($admin->canAccessBackOffice());
    }

    public function test_accounting_is_not_mobile_capable_and_is_back_office_capable_but_not_admin_capable(): void
    {
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);

        $this->assertFalse($accounting->canUseMarketplace());
        $this->assertFalse($accounting->canUseMobile());
        $this->assertTrue($accounting->canAccessBackOffice());
        // Accounting reaches the /staff finance queues, but not the general
        // /admin portal (verification review, enforcement, catalog, etc.).
        $this->assertFalse($accounting->canAccessAdmin());
    }

    public function test_budget_and_cashier_are_back_office_capable_but_not_admin_or_mobile_capable(): void
    {
        foreach ([UserRole::Budget, UserRole::Cashier] as $role) {
            $staff = User::factory()->create(['role' => $role]);

            $this->assertFalse($staff->canUseMarketplace(), "{$role->value} should not be marketplace-capable");
            $this->assertFalse($staff->canUseMobile(), "{$role->value} should not be mobile-capable");
            $this->assertTrue($staff->canAccessBackOffice(), "{$role->value} should be back-office-capable");
            $this->assertFalse($staff->canAccessAdmin(), "{$role->value} should not be admin-capable");
        }
    }

    public function test_customer_is_explicitly_not_admin_capable(): void
    {
        $customer = User::factory()->create(['role' => UserRole::ServiceFinder]);

        $this->assertFalse($customer->canAccessAdmin());
        $this->assertFalse($customer->canAccessBackOffice());
    }

    /**
     * The route-group backstop (`can:access-admin` / `can:access-back-office`
     * on the /admin and /staff prefixes) rejects marketplace accounts even
     * before any individual route's own policy/FormRequest check runs.
     */
    public function test_marketplace_accounts_cannot_reach_the_admin_or_staff_route_groups(): void
    {
        $customer = User::factory()->create(['role' => UserRole::ServiceFinder]);
        $provider = User::factory()->serviceProvider()->create();

        foreach ([$customer, $provider] as $marketplaceUser) {
            $this->actingAs($marketplaceUser)->get(route('admin.dashboard'))->assertForbidden();
            $this->actingAs($marketplaceUser)->get(route('staff.withdrawals.index'))->assertForbidden();
        }
    }

    public function test_accounting_reaches_staff_and_finance_but_not_the_general_admin_portal(): void
    {
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);

        $this->actingAs($accounting)->get(route('staff.withdrawals.index'))->assertOk();
        $this->actingAs($accounting)->get(route('admin.finance.index'))->assertOk();
        $this->actingAs($accounting)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_budget_and_cashier_cannot_reach_the_general_admin_portal(): void
    {
        foreach ([UserRole::Budget, UserRole::Cashier] as $role) {
            $staff = User::factory()->create(['role' => $role]);

            $this->actingAs($staff)->get(route('admin.dashboard'))->assertForbidden();
            $this->actingAs($staff)->get(route('staff.withdrawals.index'))->assertOk();
        }
    }

    public function test_admin_reaches_both_admin_and_staff_areas(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('staff.withdrawals.index'))->assertOk();
    }
}
