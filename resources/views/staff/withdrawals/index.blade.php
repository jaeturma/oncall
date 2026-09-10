<x-layouts.app title="Withdrawal queue">
    <div class="grid gap-6">
        <x-flash />
        <div><p class="font-bold uppercase tracking-widest text-navy-800">{{ str($actingRole->value)->title() }}</p><h1 class="text-3xl font-black">Withdrawal queue</h1><p class="mt-2 text-slate-600">Requested &rarr; Accounting review &rarr; Budget approval &rarr; Cashier disbursement. You can only act on the step assigned to your role.</p></div>

        <div class="grid gap-4">
            @forelse($open as $withdrawal)
                <article class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-lg font-black">PHP {{ number_format((float) $withdrawal->amount, 2) }}</p>
                            <p class="text-sm text-slate-600">{{ $withdrawal->user->name }} &middot; {{ $withdrawal->payout_method }} &middot; {{ $withdrawal->payout_reference }}</p>
                            <p class="mt-1 text-xs text-slate-500">Requested {{ $withdrawal->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ str($withdrawal->status->value)->replace('_', ' ')->title() }}</span>
                    </div>
                    @if($withdrawal->notes)<p class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">{{ $withdrawal->notes }}</p>@endif

                    @can('review', $withdrawal)
                        <form class="mt-5 grid gap-3 border-t border-slate-200 pt-5 sm:grid-cols-[1fr_auto]" method="POST" action="{{ route('staff.withdrawals.update', $withdrawal) }}">
                            @csrf @method('PATCH')
                            <input class="rounded-lg border border-slate-300 p-2 text-sm" type="text" name="notes" maxlength="1000" placeholder="Notes (required to return or reject)">
                            <div class="flex flex-wrap gap-2">
                                <button class="rounded-lg bg-gold-400 px-4 py-2 text-sm font-bold text-navy-900 hover:bg-gold-500" name="decision" value="approve">{{ $withdrawal->status === App\Enums\WithdrawalStatus::ForDisbursement ? 'Mark disbursed' : 'Approve' }}</button>
                                <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold" name="decision" value="return">Return</button>
                                <button class="rounded-lg border border-red-300 px-4 py-2 text-sm font-bold text-red-700" name="decision" value="reject">Reject</button>
                            </div>
                            @error('notes')<span class="text-sm text-red-700 sm:col-span-2">{{ $message }}</span>@enderror
                        </form>
                    @else
                        <p class="mt-4 text-sm text-slate-500">Waiting on the {{ str($withdrawal->status->actingRole()?->value)->title() }} step.</p>
                    @endcan
                </article>
            @empty
                <div class="rounded-2xl bg-white p-8 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">Nothing in the queue.</div>
            @endforelse
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-200 p-6"><h2 class="text-xl font-black">Recently closed</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-6 py-3">User</th><th class="px-6 py-3">Amount</th><th class="px-6 py-3">Status</th><th class="px-6 py-3">Updated</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recent as $withdrawal)
                            <tr><td class="px-6 py-4">{{ $withdrawal->user->name }}</td><td class="px-6 py-4 font-bold">PHP {{ number_format((float) $withdrawal->amount, 2) }}</td><td class="px-6 py-4">{{ str($withdrawal->status->value)->replace('_', ' ')->title() }}</td><td class="px-6 py-4">{{ $withdrawal->updated_at->format('M j, Y') }}</td></tr>
                        @empty
                            <tr><td class="px-6 py-8 text-center text-slate-500" colspan="4">No closed withdrawals.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.app>
