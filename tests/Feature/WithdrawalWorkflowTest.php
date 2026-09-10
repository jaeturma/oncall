<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\WalletLedger;
use App\Services\WithdrawalWorkflow;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WithdrawalWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private WalletLedger $ledger;

    private WithdrawalWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledger = app(WalletLedger::class);
        $this->workflow = app(WithdrawalWorkflow::class);
    }

    public function test_requesting_a_withdrawal_reserves_the_amount_immediately(): void
    {
        $user = $this->fundedUser('500.00');

        $this->workflow->request($user, '200.00', 'GCash', 'GCash 0917');

        $this->assertSame('300.00', $this->ledger->availableBalance($user));
        $this->assertDatabaseHas('wallet_transactions', ['user_id' => $user->id, 'type' => WalletTransactionType::WithdrawalHold->value, 'amount' => '-200.00']);
        $this->assertSame(WithdrawalStatus::AccountingReview, Withdrawal::sole()->status);
    }

    public function test_a_second_request_cannot_exceed_the_remaining_balance(): void
    {
        $user = $this->fundedUser('500.00');
        $this->workflow->request($user, '400.00', 'GCash', 'ref');

        $this->expectExceptionMessage('exceeds the available wallet balance');
        $this->workflow->request($user, '200.00', 'GCash', 'ref');
    }

    public function test_full_workflow_through_accounting_budget_and_cashier(): void
    {
        $user = $this->fundedUser('500.00');
        $withdrawal = $this->workflow->request($user, '150.00', 'GCash', 'ref');

        $this->workflow->advance($withdrawal, $this->staff(UserRole::Accounting), 'approve');
        $this->assertSame(WithdrawalStatus::BudgetApproval, $withdrawal->refresh()->status);

        $this->workflow->advance($withdrawal, $this->staff(UserRole::Budget), 'approve');
        $this->assertSame(WithdrawalStatus::ForDisbursement, $withdrawal->refresh()->status);

        $this->workflow->advance($withdrawal, $this->staff(UserRole::Cashier), 'approve');
        $this->assertSame(WithdrawalStatus::Completed, $withdrawal->refresh()->status);

        // Balance stays down by the withdrawn amount; the ledger shows a real Withdrawal entry.
        $this->assertSame('350.00', $this->ledger->availableBalance($user));
        $this->assertDatabaseHas('wallet_transactions', ['user_id' => $user->id, 'type' => WalletTransactionType::Withdrawal->value, 'amount' => '-150.00']);
    }

    public function test_the_wrong_role_cannot_advance_a_step(): void
    {
        $user = $this->fundedUser('500.00');
        $withdrawal = $this->workflow->request($user, '150.00', 'GCash', 'ref');

        $this->expectExceptionMessage('must be handled by');
        $this->workflow->advance($withdrawal, $this->staff(UserRole::Budget), 'approve');
    }

    public function test_rejecting_a_withdrawal_returns_the_held_amount(): void
    {
        $user = $this->fundedUser('500.00');
        $withdrawal = $this->workflow->request($user, '150.00', 'GCash', 'ref');

        $this->workflow->advance($withdrawal, $this->staff(UserRole::Accounting), 'reject', 'invalid account');

        $this->assertSame(WithdrawalStatus::Rejected, $withdrawal->refresh()->status);
        $this->assertSame('500.00', $this->ledger->availableBalance($user));
    }

    public function test_owner_can_cancel_before_accounting_acts_and_gets_the_amount_back(): void
    {
        $user = $this->fundedUser('500.00');
        $withdrawal = $this->workflow->request($user, '150.00', 'GCash', 'ref');

        $this->workflow->cancel($withdrawal, $user);

        $this->assertSame(WithdrawalStatus::Cancelled, $withdrawal->refresh()->status);
        $this->assertSame('500.00', $this->ledger->availableBalance($user));
    }

    public function test_review_endpoint_rejects_a_staff_member_acting_out_of_turn(): void
    {
        $user = $this->fundedUser('500.00');
        $withdrawal = $this->workflow->request($user, '150.00', 'GCash', 'ref');

        $this->actingAs($this->staff(UserRole::Cashier))
            ->patch(route('staff.withdrawals.update', $withdrawal), ['decision' => 'approve'])
            ->assertForbidden();
    }

    private function fundedUser(string $amount): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $this->ledger->post($user, WalletTransactionType::Adjustment, $amount, description: 'test funding');

        return $user;
    }

    private function staff(UserRole $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => UserStatus::Active]);
    }
}
