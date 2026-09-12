<x-layouts.app title="Job payments to release" :eyebrow="str(auth()->user()->role->value)->lower()->ucfirst()" description="A customer has confirmed payment. Releasing moves the net earning into the provider's wallet; only release once Oncall holds the funds." wide>
    <div class="grid gap-6">
        <section>
            <h2 class="h3 mb-3">Awaiting release</h2>
            @if($awaitingRelease->isEmpty())
                <x-empty-state compact icon="document-check" title="Nothing awaiting release" message="Payments confirmed by customers will appear here." />
            @else
                <div class="grid gap-4">
                    @foreach($awaitingRelease as $payment)
                        <article class="card card-pad">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-ink">{{ $payment->provider->name }} <span class="font-normal text-ink-muted">—</span> {{ $payment->job->serviceRequest->title }}</p>
                                    <p class="mt-1 text-sm text-ink-secondary">Gross ₱{{ number_format((float) $payment->gross_amount, 2) }} &middot; fee ₱{{ number_format((float) $payment->platform_fee, 2) }} &middot; <span class="font-semibold text-ink">net ₱{{ number_format((float) $payment->net_amount, 2) }}</span></p>
                                    <p class="mt-1 text-xs text-ink-muted">Customer confirmed {{ $payment->confirmed_at?->diffForHumans() }} via {{ $payment->payment_method }} ({{ $payment->payment_reference }})</p>
                                </div>
                                <x-ui.status-badge :status="$payment->status" />
                            </div>
                            <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-line pt-5">
                                @can('release', $payment)
                                    <form method="POST" action="{{ route('staff.job-payments.update', $payment) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="release"><x-ui.button variant="primary" size="sm" data-loading-text="Releasing…">Release to wallet</x-ui.button></form>
                                @endcan
                                @can('reverse', $payment)
                                    <form class="flex items-center gap-1.5" method="POST" action="{{ route('staff.job-payments.update', $payment) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="reverse"><input class="input min-h-9 w-48 text-[13px]" name="reason" placeholder="Reason for reversal" aria-label="Reason for reversal" required><x-ui.button variant="danger" size="sm" data-loading-text="Reversing…">Reverse</x-ui.button></form>
                                @endcan
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card">
            <div class="card-header"><h2 class="h3">Recently closed</h2></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Provider</th><th class="num">Net</th><th>Status</th><th>Updated</th></tr></thead>
                <tbody>
                    @forelse($recent as $payment)
                        <tr><td>{{ $payment->provider->name }}</td><td class="num font-semibold whitespace-nowrap">₱{{ number_format((float) $payment->net_amount, 2) }}</td><td><x-ui.status-badge :status="$payment->status" /></td><td class="whitespace-nowrap text-ink-secondary">{{ $payment->updated_at->format('M j, Y') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-ink-secondary">No released or reversed payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
    </div>
</x-layouts.app>
