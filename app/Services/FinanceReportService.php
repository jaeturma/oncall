<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\JobPaymentStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Commission;
use App\Models\JobPayment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-only reporting over the wallet ledger and the money subsystems.
 * Every figure is derived; nothing here writes.
 */
class FinanceReportService
{
    /**
     * @return array<string, string>
     */
    public function reconciliation(): array
    {
        // Sum of every posted ledger entry = what users can spend/withdraw right now
        // (holds have already been subtracted from this figure).
        $currentlySpendable = $this->money(WalletTransaction::query()->posted()->sum('amount'));

        // Amounts earmarked against open withdrawal requests – still owed to users,
        // just not spendable while the request is in flight.
        $heldForWithdrawal = $this->money(
            Withdrawal::query()->whereIn('status', WithdrawalStatus::openStates())->sum('amount')
        );

        // Everything Oncall owes users: spendable plus what is held in-flight.
        $totalObligation = bcadd($currentlySpendable, $heldForWithdrawal, 2);

        // Future obligations not yet on the ledger as spendable.
        $pendingCommission = $this->money(
            Commission::query()->whereIn('status', [CommissionStatus::Pending, CommissionStatus::Approved])->sum('amount')
        );
        $confirmedJobEarnings = $this->money(
            JobPayment::query()->where('status', JobPaymentStatus::Paid)->sum('net_amount')
        );

        // What Oncall keeps: platform fees on released job payments, net of reversals.
        $platformRevenue = $this->money(
            JobPayment::query()->where('status', JobPaymentStatus::Released)->sum('platform_fee')
        );

        $disbursed = $this->money(
            Withdrawal::query()->where('status', WithdrawalStatus::Completed)->sum('amount')
        );

        return [
            'total_obligation' => $totalObligation,
            'currently_spendable' => $currentlySpendable,
            'held_for_withdrawal' => $heldForWithdrawal,
            'pending_commission_liability' => $pendingCommission,
            'confirmed_unreleased_job_earnings' => $confirmedJobEarnings,
            'platform_revenue' => $platformRevenue,
            'total_disbursed' => $disbursed,
        ];
    }

    /**
     * Daily movement bucketed by transaction type.
     *
     * @return Collection<int, object{day: string, type: string, total: string, entries: int}>
     */
    public function ledgerMovement(Carbon $from, Carbon $to): Collection
    {
        return WalletTransaction::query()
            ->selectRaw('date(created_at) as day, type, sum(amount) as total, count(*) as entries')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('day', 'type')
            ->orderByDesc('day')
            ->orderBy('type')
            ->get()
            ->map(fn ($row) => (object) [
                'day' => $row->day,
                'type' => $row->type instanceof \BackedEnum ? $row->type->value : (string) $row->type,
                'total' => $this->money($row->total),
                'entries' => (int) $row->entries,
            ]);
    }

    /**
     * @return Collection<string, object{count: int, total: string}>
     */
    public function withdrawalPipeline(): Collection
    {
        return $this->pipeline(Withdrawal::query()->selectRaw('status, count(*) as c, sum(amount) as t')->groupBy('status')->get());
    }

    /**
     * @return array{by_status: Collection<string, object{count: int, total: string}>, gross: string, fees: string, net: string}
     */
    public function jobPaymentSummary(): array
    {
        return [
            'by_status' => $this->pipeline(JobPayment::query()->selectRaw('status, count(*) as c, sum(net_amount) as t')->groupBy('status')->get()),
            'gross' => $this->money(JobPayment::query()->sum('gross_amount')),
            'fees' => $this->money(JobPayment::query()->sum('platform_fee')),
            'net' => $this->money(JobPayment::query()->sum('net_amount')),
        ];
    }

    /**
     * @return Collection<string, object{count: int, total: string}>
     */
    public function commissionSummary(): Collection
    {
        return $this->pipeline(Commission::query()->selectRaw('status, count(*) as c, sum(amount) as t')->groupBy('status')->get());
    }

    /**
     * @return Collection<int, object{name: string, total: string}>
     */
    public function topSponsors(int $limit = 5): Collection
    {
        return Commission::query()
            ->selectRaw('sponsor_user_id, sum(amount) as t')
            ->whereIn('status', [CommissionStatus::Approved, CommissionStatus::Available])
            ->groupBy('sponsor_user_id')
            ->orderByDesc('t')
            ->limit($limit)
            ->with('sponsor:id,name')
            ->get()
            ->map(fn ($row) => (object) ['name' => $row->sponsor?->name ?? 'Unknown', 'total' => $this->money($row->t)]);
    }

    /**
     * @return array{available: string, pending: string, transactions: Collection<int, object>}
     */
    public function userStatement(User $user): array
    {
        $running = '0.00';
        $ledger = app(WalletLedger::class);

        $transactions = $user->walletTransactions()->oldest('id')->get()->map(function (WalletTransaction $txn) use (&$running) {
            if ($txn->status === WalletTransactionStatus::Posted) {
                $running = bcadd($running, (string) $txn->amount, 2);
            }

            return (object) [
                'date' => $txn->created_at,
                'type' => $txn->type->value,
                'status' => $txn->status->value,
                'amount' => $this->money($txn->amount),
                'running' => $running,
                'description' => $txn->description,
            ];
        });

        return [
            'available' => $ledger->availableBalance($user),
            'pending' => $ledger->pendingBalance($user),
            'transactions' => $transactions,
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<string, object{count: int, total: string}>
     */
    private function pipeline(Collection $rows): Collection
    {
        return $rows->mapWithKeys(function ($row): array {
            $key = $row->status instanceof \BackedEnum ? $row->status->value : (string) $row->status;

            return [$key => (object) ['count' => (int) $row->c, 'total' => $this->money($row->t)]];
        });
    }

    private function money(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
