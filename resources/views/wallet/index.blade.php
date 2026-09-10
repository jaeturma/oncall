<x-layouts.app title="Wallet">
    <div class="grid gap-6">
        @if(session('status'))<div class="rounded-xl border border-gold-200 bg-gold-50 p-4 font-semibold text-navy-900">{{ session('status') }}</div>@endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border-t-4 border-gold-400 bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Available balance</p>
                <p class="mt-1 text-3xl font-black">PHP {{ number_format((float) $availableBalance, 2) }}</p>
                <a class="mt-3 inline-flex text-sm font-bold text-navy-800 hover:underline" href="{{ route('withdrawals.index') }}">Request a withdrawal &rarr;</a>
            </div>
            <div class="rounded-2xl border-t-4 border-navy-800 bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Pending (awaiting approval)</p>
                <p class="mt-1 text-3xl font-black text-slate-500">PHP {{ number_format((float) $pendingBalance, 2) }}</p>
                <p class="mt-3 text-sm text-slate-500">Unapproved commissions. Not yet withdrawable.</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-200 p-6"><h2 class="text-xl font-black">Ledger</h2><p class="mt-1 text-sm text-slate-600">Every entry is permanent. Corrections are posted as new reversal entries.</p></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-6 py-3">Date</th><th class="px-6 py-3">Type</th><th class="px-6 py-3">Description</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Amount</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($transactions as $txn)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $txn->created_at->format('M j, Y') }}</td>
                                <td class="px-6 py-4">{{ str($txn->type->value)->replace('_', ' ')->title() }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $txn->description }}</td>
                                <td class="px-6 py-4">{{ str($txn->status->value)->title() }}</td>
                                <td class="px-6 py-4 text-right font-bold {{ bccomp((string) $txn->amount, '0', 2) === -1 ? 'text-red-700' : 'text-navy-900' }}">{{ number_format((float) $txn->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-8 text-center text-slate-500" colspan="5">No wallet activity yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <div>{{ $transactions->links() }}</div>
    </div>
</x-layouts.app>
