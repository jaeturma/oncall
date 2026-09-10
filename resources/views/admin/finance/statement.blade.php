<x-layouts.admin :title="'Statement · '.$statementUser->name">
    <div class="grid gap-6">
        <div>
            <h1 class="text-3xl font-black">{{ $statementUser->name }}</h1>
            <p class="mt-2 text-slate-600">{{ $statementUser->email }} &middot; {{ str($statementUser->role->value)->replace('_', ' ')->title() }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border-t-4 border-gold-400 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Available balance</p><p class="mt-1 text-2xl font-black">PHP {{ number_format((float) $statement['available'], 2) }}</p></div>
            <div class="rounded-2xl border-t-4 border-navy-800 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Pending (unapproved)</p><p class="mt-1 text-2xl font-black text-slate-500">PHP {{ number_format((float) $statement['pending'], 2) }}</p></div>
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-200 p-6"><h2 class="text-xl font-black">Full ledger</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-6 py-3">Date</th><th class="px-6 py-3">Type</th><th class="px-6 py-3">Description</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Amount</th><th class="px-6 py-3 text-right">Running</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($statement['transactions'] as $txn)
                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap">{{ $txn->date->format('M j, Y') }}</td>
                                <td class="px-6 py-3">{{ str($txn->type)->replace('_', ' ')->title() }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ $txn->description }}</td>
                                <td class="px-6 py-3">{{ str($txn->status)->title() }}</td>
                                <td class="px-6 py-3 text-right font-bold {{ bccomp($txn->amount, '0', 2) === -1 ? 'text-red-700' : 'text-navy-900' }}">{{ number_format((float) $txn->amount, 2) }}</td>
                                <td class="px-6 py-3 text-right text-slate-600">{{ number_format((float) $txn->running, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-8 text-center text-slate-500" colspan="6">No wallet activity.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
