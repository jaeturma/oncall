<x-layouts.admin title="Ledger movement">
    <div class="grid gap-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><h1 class="text-3xl font-black">Ledger movement</h1><p class="mt-2 text-slate-600">Daily wallet-ledger totals by entry type.</p></div>
            <a class="rounded-lg bg-navy-900 px-4 py-2 text-sm font-bold text-white" href="{{ route('admin.finance.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Export this range</a>
        </div>

        <form class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 ring-1 ring-slate-200" method="GET">
            <label class="grid gap-1 text-sm font-semibold">From<input class="rounded-lg border border-slate-300 p-2" type="date" name="from" value="{{ $from->toDateString() }}"></label>
            <label class="grid gap-1 text-sm font-semibold">To<input class="rounded-lg border border-slate-300 p-2" type="date" name="to" value="{{ $to->toDateString() }}"></label>
            <button class="rounded-lg bg-gold-400 px-4 py-2 font-bold text-navy-900">Apply</button>
        </form>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-6 py-3">Date</th><th class="px-6 py-3">Type</th><th class="px-6 py-3 text-right">Entries</th><th class="px-6 py-3 text-right">Total (PHP)</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($movement as $row)
                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap">{{ $row->day }}</td>
                                <td class="px-6 py-3">{{ str($row->type)->replace('_', ' ')->title() }}</td>
                                <td class="px-6 py-3 text-right">{{ $row->entries }}</td>
                                <td class="px-6 py-3 text-right font-bold {{ bccomp($row->total, '0', 2) === -1 ? 'text-red-700' : 'text-navy-900' }}">{{ number_format((float) $row->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-8 text-center text-slate-500" colspan="4">No ledger activity in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
