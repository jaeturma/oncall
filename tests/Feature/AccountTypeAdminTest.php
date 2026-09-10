<?php

namespace Tests\Feature;

use App\Enums\CommissionType;
use App\Enums\UserRole;
use App\Models\AccountType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccountTypeAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_an_account_type_with_a_commission_rule(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('admin.account-types.store'), [
            'name' => 'Verified Provider',
            'registration_fee' => '500',
            'sponsor_commission_type' => CommissionType::Percentage->value,
            'sponsor_commission_value' => '10',
            'requires_identity_verification' => '1',
            'active' => '1',
        ])->assertRedirect(route('admin.account-types.index'));

        $accountType = AccountType::sole();
        $this->assertSame('verified-provider', $accountType->slug);
        $this->assertSame('50.00', $accountType->sponsorCommissionAmount());
    }

    public function test_percentage_commission_over_one_hundred_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('admin.account-types.store'), [
            'name' => 'Bad', 'registration_fee' => '100',
            'sponsor_commission_type' => CommissionType::Percentage->value, 'sponsor_commission_value' => '150',
        ])->assertSessionHasErrors('sponsor_commission_value');
    }

    public function test_duplicate_name_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        AccountType::create(['name' => 'Standard', 'slug' => 'standard', 'registration_fee' => 0, 'sponsor_commission_type' => CommissionType::None, 'sponsor_commission_value' => 0]);

        $this->actingAs($admin)->post(route('admin.account-types.store'), [
            'name' => 'Standard', 'registration_fee' => '0',
            'sponsor_commission_type' => CommissionType::None->value, 'sponsor_commission_value' => '0',
        ])->assertSessionHasErrors('name');
    }

    public function test_non_admin_cannot_manage_account_types(): void
    {
        $user = User::factory()->create(['role' => UserRole::ServiceProvider]);

        $this->actingAs($user)->get(route('admin.account-types.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.account-types.store'), [])->assertForbidden();
    }

    public function test_admin_can_toggle_an_account_type(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $accountType = AccountType::create(['name' => 'X', 'slug' => 'x', 'registration_fee' => 0, 'sponsor_commission_type' => CommissionType::None, 'sponsor_commission_value' => 0, 'active' => true]);

        $this->actingAs($admin)->patch(route('admin.account-types.toggle', $accountType))->assertRedirect();

        $this->assertFalse($accountType->refresh()->active);
    }
}
