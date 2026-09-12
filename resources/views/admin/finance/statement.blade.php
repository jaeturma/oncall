<x-layouts.admin :title="'Statement · '.$statementUser->name" :description="$statementUser->email.' · '.str($statementUser->role->value)->replace('_', ' ')->lower()->ucfirst()">
    <div class="grid gap-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.stat-card label="Available balance" :value="'₱'.number_format((float) $statement['available'], 2)" icon="wallet" tone="accent" />
            <x-ui.stat-card label="Pending (unapproved)" :value="'₱'.number_format((float) $statement['pending'], 2)" icon="clock" />
        </div>

        <section class="card">
            <div class="card-header"><h2 class="h3">Full ledger</h2></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Status</th><th class="num">Amount</th><th class="num">Running</th></tr></thead>
                <tbody>
                    @forelse($statement['transactions'] as $txn)
                        <tr>
                            <td class="whitespace-nowrap">{{ $txn->date->format('M j, Y') }}</td>
                            <td class="whitespace-nowrap">{{ str($txn->type)->replace('_', ' ')->lower()->ucfirst() }}</td>
                            <td class="text-ink-secondary">{{ $txn->description }}</td>
                            <td><x-ui.status-badge :status="$txn->status" /></td>
                            <td class="num font-semibold whitespace-nowrap {{ bccomp($txn->amount, '0', 2) === -1 ? 'text-danger-700' : 'text-ink' }}">{{ number_format((float) $txn->amount, 2) }}</td>
                            <td class="num whitespace-nowrap text-ink-secondary">{{ number_format((float) $txn->running, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-ink-secondary">No wallet activity.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
    </div>
</x-layouts.admin>
