<x-layouts.app title="Wallet" description="Commissions and released job earnings land here. Every ledger entry is permanent; corrections are posted as new entries.">
    <x-slot:actions><x-ui.button :href="route('withdrawals.index')" variant="primary" icon="banknotes">Cash out</x-ui.button></x-slot:actions>

    <div class="grid gap-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.stat-card label="Available balance" :value="'₱'.number_format((float) $availableBalance, 2)" hint="Ready to withdraw" icon="wallet" tone="accent" />
            <x-ui.stat-card label="Pending" :value="'₱'.number_format((float) $pendingBalance, 2)" hint="Awaiting approval, not yet withdrawable" icon="clock" />
        </div>

        <section class="card">
            <div class="card-header"><h2 class="h3">Ledger</h2></div>
            @if($transactions->isEmpty())
                <div class="px-5 py-10 text-center sm:px-6"><p class="font-medium text-ink">No wallet activity yet</p><p class="mt-1 text-sm text-ink-secondary">Approved commissions and released job earnings will appear here.</p></div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Status</th><th class="num">Amount</th></tr></thead>
                        <tbody>
                            @foreach($transactions as $txn)
                                @php($negative = bccomp((string) $txn->amount, '0', 2) === -1)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $txn->created_at->format('M j, Y') }}</td>
                                    <td class="whitespace-nowrap font-medium">{{ str($txn->type->value)->replace('_', ' ')->lower()->ucfirst() }}</td>
                                    <td class="text-ink-secondary">{{ $txn->description }}</td>
                                    <td><x-ui.status-badge :status="$txn->status" /></td>
                                    <td class="num font-semibold whitespace-nowrap {{ $negative ? 'text-danger-700' : 'text-success-800' }}">{{ $negative ? '−' : '+' }}₱{{ number_format(abs((float) $txn->amount), 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
        @if($transactions->hasPages())<div>{{ $transactions->links() }}</div>@endif
    </div>
</x-layouts.app>
