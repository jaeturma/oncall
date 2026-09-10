<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Services\FinanceReportService;
use App\Services\WalletLedger;
use App\Services\WithdrawalWorkflow;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FinanceReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reconciliation_derives_obligation_revenue_and_holds_from_the_ledger(): void
    {
        $ledger = app(WalletLedger::class);
        $a = User::factory()->create();
        $b = User::factory()->create(['status' => UserStatus::Active]);

        $ledger->post($a, WalletTransactionType::Adjustment, '1000.00');
        $ledger->post($b, WalletTransactionType::Adjustment, '500.00');
        app(WithdrawalWorkflow::class)->request($b, '200.00', 'GCash', 'ref');

        $report = app(FinanceReportService::class)->reconciliation();

        // a: 1000 spendable, b: 500 - 200 hold = 300 spendable
        $this->assertSame('1300.00', $report['currently_spendable']);
        $this->assertSame('200.00', $report['held_for_withdrawal']);
        $this->assertSame('1500.00', $report['total_obligation']);
    }

    public function test_ledger_movement_buckets_by_day_and_type(): void
    {
        $ledger = app(WalletLedger::class);
        $user = User::factory()->create();
        $ledger->post($user, WalletTransactionType::Commission, '10.00');
        $ledger->post($user, WalletTransactionType::Commission, '15.00');
        $ledger->post($user, WalletTransactionType::Adjustment, '-5.00');

        $movement = app(FinanceReportService::class)->ledgerMovement(now()->subDay(), now());

        $commission = $movement->firstWhere('type', WalletTransactionType::Commission->value);
        $this->assertSame(2, $commission->entries);
        $this->assertSame('25.00', $commission->total);
    }

    public function test_user_statement_shows_a_running_posted_balance(): void
    {
        $ledger = app(WalletLedger::class);
        $user = User::factory()->create();
        $ledger->post($user, WalletTransactionType::Commission, '100.00');
        $ledger->post($user, WalletTransactionType::Commission, '40.00', WalletTransactionStatus::Pending);
        $ledger->post($user, WalletTransactionType::Adjustment, '-30.00');

        $statement = app(FinanceReportService::class)->userStatement($user);

        $this->assertSame('70.00', $statement['available']);
        $this->assertSame('40.00', $statement['pending']);
        $this->assertSame('70.00', $statement['transactions']->last()->running);
    }

    public function test_only_admin_or_accounting_can_open_the_finance_area(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ServiceProvider]))->get(route('admin.finance.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => UserRole::Budget]))->get(route('admin.finance.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => UserRole::Accounting]))->get(route('admin.finance.index'))->assertOk();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))->get(route('admin.finance.index'))->assertOk();
    }

    public function test_csv_export_streams_the_ledger_movement(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create();
        app(WalletLedger::class)->post($user, WalletTransactionType::Commission, '99.00');

        $response = $this->actingAs($admin)->get(route('admin.finance.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Date,Type,Entries', $response->streamedContent());
        $this->assertStringContainsString('COMMISSION,1,99.00', $response->streamedContent());
    }
}
