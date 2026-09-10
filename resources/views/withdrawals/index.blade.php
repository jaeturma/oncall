<x-layouts.app title="Withdrawals">
    <div class="grid gap-6">
        <x-flash />

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <h1 class="text-2xl font-black">Cash out</h1>
                <p class="text-sm text-slate-600">Available: <span class="font-bold text-navy-900">PHP {{ number_format((float) $availableBalance, 2) }}</span></p>
            </div>
            <p class="mt-2 text-sm text-slate-600">Requesting a withdrawal reserves the amount immediately. It then goes through Accounting review, Budget approval, and Cashier disbursement.</p>

            @if($canRequest && bccomp((string) $availableBalance, '1', 2) >= 0)
                <form class="mt-6 grid gap-4 sm:grid-cols-2" method="POST" action="{{ route('withdrawals.store') }}">
                    @csrf
                    <label class="grid gap-2 font-semibold">Amount (PHP)
                        <input class="rounded-lg border border-slate-300 p-3" type="number" name="amount" min="1" max="{{ $availableBalance }}" step="0.01" value="{{ old('amount') }}" required>
                        @error('amount')<span class="text-sm font-normal text-red-700">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-2 font-semibold">Payout method
                        <select class="rounded-lg border border-slate-300 p-3" name="payout_method" required>
                            <option value="GCash" @selected(old('payout_method') === 'GCash')>GCash</option>
                            <option value="Bank transfer" @selected(old('payout_method') === 'Bank transfer')>Bank transfer</option>
                            <option value="Maya" @selected(old('payout_method') === 'Maya')>Maya</option>
                        </select>
                        @error('payout_method')<span class="text-sm font-normal text-red-700">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-2 font-semibold sm:col-span-2">Payout account reference
                        <input class="rounded-lg border border-slate-300 p-3" type="text" name="payout_reference" maxlength="120" placeholder="e.g. GCash 09XX XXX 1234 / Bank acct name & number" value="{{ old('payout_reference') }}" required>
                        @error('payout_reference')<span class="text-sm font-normal text-red-700">{{ $message }}</span>@enderror
                    </label>
                    <button class="justify-self-start rounded-lg bg-gold-400 px-6 py-3 font-bold text-navy-900 hover:bg-gold-500 sm:col-span-2">Submit withdrawal request</button>
                </form>
            @elseif(! $canRequest)
                <p class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">Withdrawals are unavailable while your account has an active restriction.</p>
            @else
                <p class="mt-6 text-sm text-slate-500">You need at least PHP 1.00 available to request a withdrawal.</p>
            @endif
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-200 p-6"><h2 class="text-xl font-black">Your requests</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-6 py-3">Requested</th><th class="px-6 py-3">Amount</th><th class="px-6 py-3">Method</th><th class="px-6 py-3">Status</th><th class="px-6 py-3">Notes</th><th class="px-6 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($withdrawals as $withdrawal)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $withdrawal->created_at->format('M j, Y') }}</td>
                                <td class="px-6 py-4 font-bold">PHP {{ number_format((float) $withdrawal->amount, 2) }}</td>
                                <td class="px-6 py-4">{{ $withdrawal->payout_method }}</td>
                                <td class="px-6 py-4">{{ str($withdrawal->status->value)->replace('_', ' ')->title() }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $withdrawal->notes }}</td>
                                <td class="px-6 py-4 text-right">
                                    @can('cancel', $withdrawal)
                                        <form method="POST" action="{{ route('withdrawals.cancel', $withdrawal) }}">@csrf @method('PATCH')<button class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-bold">Cancel</button></form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-8 text-center text-slate-500" colspan="6">No withdrawal requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <div>{{ $withdrawals->links() }}</div>
    </div>
</x-layouts.app>
