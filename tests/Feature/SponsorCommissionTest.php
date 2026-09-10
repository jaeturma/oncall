<?php

namespace Tests\Feature;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\AccountType;
use App\Models\Commission;
use App\Models\User;
use App\Services\WalletLedger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SponsorCommissionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_verifying_a_sponsored_user_posts_a_pending_commission_and_pending_ledger_entry(): void
    {
        [$sponsor, $sponsored] = $this->sponsoredPair(CommissionType::Percentage, '10', registrationFee: '500');

        $sponsored->update(['identity_verification_status' => VerificationStatus::Verified]);

        $commission = Commission::sole();
        $this->assertSame('50.00', (string) $commission->amount);
        $this->assertSame(CommissionStatus::Pending, $commission->status);
        $this->assertSame('0.00', app(WalletLedger::class)->availableBalance($sponsor));
        $this->assertSame('50.00', app(WalletLedger::class)->pendingBalance($sponsor));
    }

    public function test_a_fixed_commission_uses_the_configured_amount(): void
    {
        [, $sponsored] = $this->sponsoredPair(CommissionType::Fixed, '250', registrationFee: '999');

        $sponsored->update(['identity_verification_status' => VerificationStatus::Verified]);

        $this->assertSame('250.00', (string) Commission::sole()->amount);
    }

    public function test_approving_a_commission_releases_it_into_the_sponsor_wallet(): void
    {
        [$sponsor, $sponsored] = $this->sponsoredPair(CommissionType::Fixed, '150');
        $sponsored->update(['identity_verification_status' => VerificationStatus::Verified]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->patch(route('admin.commissions.update', Commission::sole()), ['decision' => 'approve'])->assertRedirect();

        $this->assertSame(CommissionStatus::Available, Commission::sole()->status);
        $this->assertSame('150.00', app(WalletLedger::class)->availableBalance($sponsor->refresh()));
    }

    public function test_reversing_an_approved_commission_removes_it_from_the_balance(): void
    {
        [$sponsor, $sponsored] = $this->sponsoredPair(CommissionType::Fixed, '150');
        $sponsored->update(['identity_verification_status' => VerificationStatus::Verified]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->patch(route('admin.commissions.update', Commission::sole()), ['decision' => 'approve']);

        $this->actingAs($admin)->patch(route('admin.commissions.update', Commission::sole()), ['decision' => 'reverse', 'reason' => 'fraud'])->assertRedirect();

        $this->assertSame(CommissionStatus::Reversed, Commission::sole()->status);
        $this->assertSame('0.00', app(WalletLedger::class)->availableBalance($sponsor->refresh()));
    }

    public function test_no_commission_without_a_sponsor_or_without_a_commission_rule(): void
    {
        $noRule = AccountType::create(['name' => 'Free', 'slug' => 'free', 'registration_fee' => 0, 'sponsor_commission_type' => CommissionType::None, 'sponsor_commission_value' => 0]);
        $sponsor = User::factory()->create();

        $noSponsor = User::factory()->create(['account_type_id' => $this->ruleType()->id]);
        $noSponsor->update(['identity_verification_status' => VerificationStatus::Verified]);

        $noRuleUser = User::factory()->create(['sponsor_user_id' => $sponsor->id, 'account_type_id' => $noRule->id]);
        $noRuleUser->update(['identity_verification_status' => VerificationStatus::Verified]);

        $this->assertSame(0, Commission::count());
    }

    public function test_commission_is_single_level_only(): void
    {
        [$sponsor, $child] = $this->sponsoredPair(CommissionType::Fixed, '100');
        $child->update(['identity_verification_status' => VerificationStatus::Verified]);
        $this->assertSame(1, $sponsor->commissionsEarned()->count());

        // The child sponsors a grandchild – the original sponsor earns nothing more.
        $grandchild = User::factory()->create(['sponsor_user_id' => $child->id, 'account_type_id' => $this->ruleType('200')->id]);
        $grandchild->update(['identity_verification_status' => VerificationStatus::Verified]);

        $this->assertSame(1, $sponsor->commissionsEarned()->count());
        $this->assertSame(1, $child->commissionsEarned()->count());
    }

    public function test_a_user_is_never_commissioned_twice_for_the_same_trigger(): void
    {
        [, $sponsored] = $this->sponsoredPair(CommissionType::Fixed, '100');
        $sponsored->update(['identity_verification_status' => VerificationStatus::Verified]);
        $sponsored->update(['identity_verification_status' => VerificationStatus::Rejected]);
        $sponsored->update(['identity_verification_status' => VerificationStatus::Verified]);

        $this->assertSame(1, Commission::count());
    }

    private function ruleType(string $value = '100'): AccountType
    {
        return AccountType::create([
            'name' => 'Rule '.$value,
            'slug' => 'rule-'.$value.'-'.uniqid(),
            'registration_fee' => 500,
            'sponsor_commission_type' => CommissionType::Fixed,
            'sponsor_commission_value' => $value,
        ]);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function sponsoredPair(CommissionType $type, string $value, string $registrationFee = '500'): array
    {
        $accountType = AccountType::create([
            'name' => 'Sponsored '.uniqid(),
            'slug' => 'sponsored-'.uniqid(),
            'registration_fee' => $registrationFee,
            'sponsor_commission_type' => $type,
            'sponsor_commission_value' => $value,
        ]);
        $sponsor = User::factory()->create();
        $sponsored = User::factory()->create(['sponsor_user_id' => $sponsor->id, 'account_type_id' => $accountType->id]);

        return [$sponsor, $sponsored];
    }
}
