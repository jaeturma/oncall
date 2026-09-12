@php($peso = fn ($v): string => '₱'.number_format((float) $v, 2))

<x-layouts.admin title="Finance and reconciliation" description="Every figure is derived from the wallet ledger. Nothing on this page changes data.">
    <x-slot:actions>
        <x-ui.button :href="route('admin.finance.ledger')" variant="secondary" icon="chart">Ledger movement</x-ui.button>
        <x-ui.button :href="route('admin.finance.export')" variant="dark" icon="document-check">Export CSV</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-6">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <x-ui.stat-card label="Total owed to users" :value="$peso($reconciliation['total_obligation'])" :hint="'Spendable '.$peso($reconciliation['currently_spendable']).' · held for withdrawal '.$peso($reconciliation['held_for_withdrawal'])" icon="wallet" tone="brand" />
            <x-ui.stat-card label="Platform revenue" :value="$peso($reconciliation['platform_revenue'])" hint="Released job fees" icon="chart" tone="accent" />
            <x-ui.stat-card label="Total disbursed to date" :value="$peso($reconciliation['total_disbursed'])" icon="banknotes" tone="success" />
            <x-ui.stat-card label="Pending commission liability" :value="$peso($reconciliation['pending_commission_liability'])" hint="Posted but not yet approved" icon="clock" />
            <x-ui.stat-card label="Confirmed earnings not yet released" :value="$peso($reconciliation['confirmed_unreleased_job_earnings'])" icon="document-check" />
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="card">
                <div class="card-header"><h2 class="h3">Withdrawal pipeline</h2></div>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Status</th><th class="num">Count</th><th class="num">Amount</th></tr></thead>
                    <tbody>
                        @forelse($withdrawalPipeline as $status => $row)
                            <tr><td><x-ui.status-badge :status="$status" /></td><td class="num">{{ $row->count }}</td><td class="num font-semibold">{{ $peso($row->total) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-ink-secondary">No withdrawals.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </section>

            <section class="card">
                <div class="card-header"><div><h2 class="h3">Job payments</h2><p class="mt-0.5 text-xs text-ink-muted">Gross {{ $peso($jobPayments['gross']) }} &middot; fees {{ $peso($jobPayments['fees']) }} &middot; provider net {{ $peso($jobPayments['net']) }}</p></div></div>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Status</th><th class="num">Count</th><th class="num">Net</th></tr></thead>
                    <tbody>
                        @forelse($jobPayments['by_status'] as $status => $row)
                            <tr><td><x-ui.status-badge :status="$status" /></td><td class="num">{{ $row->count }}</td><td class="num font-semibold">{{ $peso($row->total) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-ink-secondary">No job payments.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="h3">Commissions</h2></div>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Status</th><th class="num">Count</th><th class="num">Amount</th></tr></thead>
                    <tbody>
                        @forelse($commissions as $status => $row)
                            <tr><td><x-ui.status-badge :status="$status" /></td><td class="num">{{ $row->count }}</td><td class="num font-semibold">{{ $peso($row->total) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-ink-secondary">No commissions.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="h3">Top sponsors by commission</h2></div>
                <div class="table-wrap"><table class="table">
                    <tbody>
                        @forelse($topSponsors as $sponsor)
                            <tr><td>{{ $sponsor->name }}</td><td class="num font-semibold">{{ $peso($sponsor->total) }}</td></tr>
                        @empty
                            <tr><td class="py-6 text-center text-ink-secondary">No approved commissions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </section>
        </div>

        <section class="card">
            <div class="card-header"><h2 class="h3">Ledger movement — last 14 days</h2><a class="text-sm font-semibold text-navy-800 hover:underline" href="{{ route('admin.finance.ledger') }}">Full history</a></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Date</th><th>Type</th><th class="num">Entries</th><th class="num">Total</th></tr></thead>
                <tbody>
                    @forelse($recentMovement as $row)
                        <tr>
                            <td class="whitespace-nowrap">{{ $row->day }}</td>
                            <td class="whitespace-nowrap">{{ str($row->type)->replace('_', ' ')->lower()->ucfirst() }}</td>
                            <td class="num">{{ $row->entries }}</td>
                            <td class="num font-semibold {{ bccomp($row->total, '0', 2) === -1 ? 'text-danger-700' : 'text-ink' }}">{{ number_format((float) $row->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-ink-secondary">No ledger activity in the last 14 days.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
    </div>
</x-layouts.admin>
