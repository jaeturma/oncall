<?php

namespace Tests\Feature;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Services\WalletLedger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WalletLedgerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private WalletLedger $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledger = app(WalletLedger::class);
    }

    public function test_available_balance_is_the_sum_of_posted_entries_only(): void
    {
        $user = User::factory()->create();

        $this->ledger->post($user, WalletTransactionType::Commission, '100.00');
        $this->ledger->post($user, WalletTransactionType::Adjustment, '25.50');
        $this->ledger->post($user, WalletTransactionType::WithdrawalHold, '-40.00');
        $this->ledger->post($user, WalletTransactionType::Commission, '999.00', WalletTransactionStatus::Pending);

        $this->assertSame('85.50', $this->ledger->availableBalance($user));
        $this->assertSame('999.00', $this->ledger->pendingBalance($user));
    }

    public function test_releasing_a_pending_entry_moves_it_into_the_available_balance(): void
    {
        $user = User::factory()->create();
        $entry = $this->ledger->post($user, WalletTransactionType::Commission, '75.00', WalletTransactionStatus::Pending);

        $this->assertSame('0.00', $this->ledger->availableBalance($user));

        $this->ledger->release($entry);

        $this->assertSame('75.00', $this->ledger->availableBalance($user));
    }

    public function test_reversing_a_posted_entry_appends_an_opposing_entry_and_keeps_the_original(): void
    {
        $user = User::factory()->create();
        $entry = $this->ledger->post($user, WalletTransactionType::Commission, '120.00');

        $reversal = $this->ledger->reverse($entry, 'clawed back');

        $this->assertSame('0.00', $this->ledger->availableBalance($user));
        $this->assertSame('-120.00', (string) $reversal->amount);
        $this->assertSame(WalletTransactionStatus::Posted, $entry->refresh()->status);
        $this->assertDatabaseCount('wallet_transactions', 2);
    }

    public function test_a_posted_entry_cannot_be_voided(): void
    {
        $user = User::factory()->create();
        $entry = $this->ledger->post($user, WalletTransactionType::Commission, '10.00');

        $this->expectException(\RuntimeException::class);
        $this->ledger->void($entry);
    }
}
