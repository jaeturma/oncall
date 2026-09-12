<x-layouts.app title="Cash out" eyebrow="Wallet" description="Requesting a withdrawal reserves the amount immediately. It then goes through Accounting review, Budget approval, and Cashier disbursement.">
    <div class="grid gap-6">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <section class="card card-pad">
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <h2 class="h3">Request a withdrawal</h2>
                    <p class="text-sm text-ink-secondary">Available: <span class="font-semibold text-ink tabular-nums">₱{{ number_format((float) $availableBalance, 2) }}</span></p>
                </div>

                @if($canRequest && bccomp((string) $availableBalance, '1', 2) >= 0)
                    <form class="mt-5 grid gap-5 sm:grid-cols-2" method="POST" action="{{ route('withdrawals.store') }}">
                        @csrf
                        <x-form.input name="amount" type="number" label="Amount (₱)" min="1" :max="$availableBalance" step="0.01" inputmode="decimal" placeholder="0.00" required :hint="'Up to ₱'.number_format((float) $availableBalance, 2)" />
                        <x-form.select name="payout_method" label="Payout method" required>
                            @foreach(['GCash', 'Maya', 'Bank transfer'] as $method)<option value="{{ $method }}" @selected(old('payout_method') === $method)>{{ $method }}</option>@endforeach
                        </x-form.select>
                        <x-form.input name="payout_reference" label="Payout account" maxlength="120" placeholder="e.g. GCash 09XX XXX 1234 or bank account name and number" required wrapper-class="sm:col-span-2" hint="Only staff processing your payout can see this." />
                        <div class="sm:col-span-2"><x-ui.button variant="primary" size="lg" data-loading-text="Submitting…">Submit withdrawal request</x-ui.button></div>
                    </form>
                @elseif(! $canRequest)
                    <x-ui.alert tone="warning" class="mt-5">Withdrawals are unavailable while your account has an active restriction.</x-ui.alert>
                @else
                    <x-ui.alert tone="info" class="mt-5">You need at least ₱1.00 available to request a withdrawal.</x-ui.alert>
                @endif
            </section>

            <aside class="card card-pad text-sm">
                <p class="font-semibold text-ink">How payouts work</p>
                <ol class="mt-3 grid gap-3">
                    @foreach([['Requested', 'The amount is reserved from your balance.'], ['Accounting review', 'Staff check the request and payout details.'], ['Budget approval', 'The payout is approved for release.'], ['Cashier disbursement', 'Funds are sent to your payout account.']] as $index => [$step, $text])
                        <li class="flex gap-3"><span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-navy-50 text-xs font-bold text-navy-800">{{ $index + 1 }}</span><span><span class="block font-medium text-ink">{{ $step }}</span><span class="text-ink-secondary">{{ $text }}</span></span></li>
                    @endforeach
                </ol>
            </aside>
        </div>

        <section class="card">
            <div class="card-header"><h2 class="h3">Your requests</h2></div>
            @if($withdrawals->isEmpty())
                <div class="px-5 py-10 text-center sm:px-6"><p class="font-medium text-ink">No withdrawal requests yet</p><p class="mt-1 text-sm text-ink-secondary">Requests you submit will show their progress here.</p></div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Requested</th><th class="num">Amount</th><th>Method</th><th>Status</th><th>Notes</th><th><span class="sr-only">Actions</span></th></tr></thead>
                        <tbody>
                            @foreach($withdrawals as $withdrawal)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $withdrawal->created_at->format('M j, Y') }}</td>
                                    <td class="num font-semibold whitespace-nowrap">₱{{ number_format((float) $withdrawal->amount, 2) }}</td>
                                    <td class="whitespace-nowrap">{{ $withdrawal->payout_method }}</td>
                                    <td><x-ui.status-badge :status="$withdrawal->status" /></td>
                                    <td class="text-ink-secondary">{{ $withdrawal->notes }}</td>
                                    <td class="text-right">
                                        @can('cancel', $withdrawal)
                                            <form method="POST" action="{{ route('withdrawals.cancel', $withdrawal) }}">@csrf @method('PATCH')<x-ui.button variant="secondary" size="sm" data-loading-text="Cancelling…">Cancel</x-ui.button></form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
        @if($withdrawals->hasPages())<div>{{ $withdrawals->links() }}</div>@endif
    </div>
</x-layouts.app>
