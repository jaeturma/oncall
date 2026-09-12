<x-layouts.admin title="Ledger movement" description="Daily wallet-ledger totals by entry type.">
    <x-slot:actions><x-ui.button :href="route('admin.finance.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()])" variant="dark" icon="document-check">Export this range</x-ui.button></x-slot:actions>

    <div class="grid gap-6">
        <form class="card grid gap-3 p-4 sm:grid-cols-[12rem_12rem_auto] sm:items-end" method="GET" data-skip-loading>
            <x-form.field name="from" label="From" for="from"><input id="from" class="input" type="date" name="from" value="{{ $from->toDateString() }}"></x-form.field>
            <x-form.field name="to" label="To" for="to"><input id="to" class="input" type="date" name="to" value="{{ $to->toDateString() }}"></x-form.field>
            <x-ui.button variant="dark">Apply</x-ui.button>
        </form>

        <section class="card">
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Date</th><th>Type</th><th class="num">Entries</th><th class="num">Total (₱)</th></tr></thead>
                <tbody>
                    @forelse($movement as $row)
                        <tr>
                            <td class="whitespace-nowrap">{{ $row->day }}</td>
                            <td class="whitespace-nowrap">{{ str($row->type)->replace('_', ' ')->lower()->ucfirst() }}</td>
                            <td class="num">{{ $row->entries }}</td>
                            <td class="num font-semibold {{ bccomp($row->total, '0', 2) === -1 ? 'text-danger-700' : 'text-ink' }}">{{ number_format((float) $row->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-10 text-center text-ink-secondary">No ledger activity in this range.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
    </div>
</x-layouts.admin>
