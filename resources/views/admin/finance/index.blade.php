<x-layouts.admin title="Finance reports">
    @php($peso = fn ($v) => 'PHP '.number_format((float) $v, 2))
    <div class="grid gap-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><h1 class="text-3xl font-black">Finance &amp; reconciliation</h1><p class="mt-2 text-slate-600">Every figure is derived from the wallet ledger. Nothing here changes data.</p></div>
            <div class="flex gap-2">
                <a class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold" href="{{ route('admin.finance.ledger') }}">Ledger movement</a>
                <a class="rounded-lg bg-navy-900 px-4 py-2 text-sm font-bold text-white" href="{{ route('admin.finance.export') }}">Export CSV</a>
            </div>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-2xl border-t-4 border-navy-800 bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Total owed to users</p>
                <p class="mt-1 text-2xl font-black">{{ $peso($reconciliation['total_obligation']) }}</p>
                <p class="mt-2 text-xs text-slate-500">Spendable now {{ $peso($reconciliation['currently_spendable']) }} · held for withdrawal {{ $peso($reconciliation['held_for_withdrawal']) }}</p>
            </div>
            <div class="rounded-2xl border-t-4 border-gold-400 bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Platform revenue (released job fees)</p>
                <p class="mt-1 text-2xl font-black">{{ $peso($reconciliation['platform_revenue']) }}</p>
            </div>
            <div class="rounded-2xl border-t-4 border-sky-500 bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">Total disbursed to date</p>
                <p class="mt-1 text-2xl font-black">{{ $peso($reconciliation['total_disbursed']) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">Pending commission liability</p>
                <p class="mt-1 text-xl font-black text-slate-600">{{ $peso($reconciliation['pending_commission_liability']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Commissions posted but not yet approved.</p>
            </div>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">Confirmed job earnings not yet released</p>
                <p class="mt-1 text-xl font-black text-slate-600">{{ $peso($reconciliation['confirmed_unreleased_job_earnings']) }}</p>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-200 p-5"><h2 class="font-black">Withdrawal pipeline</h2></div>
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Count</th><th class="px-5 py-3 text-right">Amount</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($withdrawalPipeline as $status => $row)
                            <tr><td class="px-5 py-3">{{ str($status)->replace('_', ' ')->title() }}</td><td class="px-5 py-3 text-right">{{ $row->count }}</td><td class="px-5 py-3 text-right font-bold">{{ $peso($row->total) }}</td></tr>
                        @empty
                            <tr><td class="px-5 py-6 text-center text-slate-500" colspan="3">No withdrawals.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-200 p-5"><h2 class="font-black">Job payments</h2><p class="mt-1 text-xs text-slate-500">Gross {{ $peso($jobPayments['gross']) }} · fees {{ $peso($jobPayments['fees']) }} · provider net {{ $peso($jobPayments['net']) }}</p></div>
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Count</th><th class="px-5 py-3 text-right">Net</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($jobPayments['by_status'] as $status => $row)
                            <tr><td class="px-5 py-3">{{ str($status)->title() }}</td><td class="px-5 py-3 text-right">{{ $row->count }}</td><td class="px-5 py-3 text-right font-bold">{{ $peso($row->total) }}</td></tr>
                        @empty
                            <tr><td class="px-5 py-6 text-center text-slate-500" colspan="3">No job payments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-200 p-5"><h2 class="font-black">Commissions</h2></div>
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Count</th><th class="px-5 py-3 text-right">Amount</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($commissions as $status => $row)
                            <tr><td class="px-5 py-3">{{ str($status)->title() }}</td><td class="px-5 py-3 text-right">{{ $row->count }}</td><td class="px-5 py-3 text-right font-bold">{{ $peso($row->total) }}</td></tr>
                        @empty
                            <tr><td class="px-5 py-6 text-center text-slate-500" colspan="3">No commissions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-200 p-5"><h2 class="font-black">Top sponsors by commission</h2></div>
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topSponsors as $sponsor)
                            <tr><td class="px-5 py-3">{{ $sponsor->name }}</td><td class="px-5 py-3 text-right font-bold">{{ $peso($sponsor->total) }}</td></tr>
                        @empty
                            <tr><td class="px-5 py-6 text-center text-slate-500">No approved commissions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-200 p-5"><h2 class="font-black">Ledger movement — last 14 days</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Type</th><th class="px-5 py-3 text-right">Entries</th><th class="px-5 py-3 text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentMovement as $row)
                            <tr>
                                <td class="px-5 py-3 whitespace-nowrap">{{ $row->day }}</td>
                                <td class="px-5 py-3">{{ str($row->type)->replace('_', ' ')->title() }}</td>
                                <td class="px-5 py-3 text-right">{{ $row->entries }}</td>
                                <td class="px-5 py-3 text-right font-bold {{ bccomp($row->total, '0', 2) === -1 ? 'text-red-700' : 'text-navy-900' }}">{{ number_format((float) $row->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-5 py-6 text-center text-slate-500" colspan="4">No ledger activity in the last 14 days.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
