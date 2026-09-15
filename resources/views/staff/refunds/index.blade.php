<x-layouts.app title="Refund requests" :eyebrow="str(auth()->user()->role->value)->lower()->ucfirst()" description="Approving a refund posts a Refund ledger entry and reduces the provider's net for the job; the underlying money movement still goes through the same job-payment ledger rules." wide>
    <div class="grid gap-6">
        <section>
            <h2 class="h3 mb-3">Pending review</h2>
            @if($pending->isEmpty())
                <x-empty-state compact icon="document-check" title="Nothing pending" message="Refund requests submitted by customers or back-office staff will appear here." />
            @else
                <div class="grid gap-4">
                    @foreach($pending as $refund)
                        <article class="card card-pad">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-ink">{{ $refund->requester->name }} <span class="font-normal text-ink-muted">—</span> job payment #{{ $refund->job_payment_id }} ({{ $refund->jobPayment->provider->name }})</p>
                                    <p class="mt-1 text-sm text-ink-secondary">Requested amount <span class="font-semibold text-ink">₱{{ number_format((float) $refund->amount, 2) }}</span></p>
                                    <p class="mt-1 text-xs text-ink-muted">{{ $refund->reason }}</p>
                                </div>
                                <x-ui.status-badge :status="$refund->status" />
                            </div>
                            <div class="mt-5 flex flex-wrap items-start gap-2 border-t border-line pt-5">
                                <form method="POST" action="{{ route('staff.refunds.update', $refund) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="approve"><x-ui.button variant="primary" size="sm" data-loading-text="Approving…">Approve refund</x-ui.button></form>
                                <form class="flex items-center gap-1.5" method="POST" action="{{ route('staff.refunds.update', $refund) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="reject"><input class="input min-h-9 w-56 text-[13px]" name="notes" placeholder="Reason for rejection" aria-label="Reason for rejection" required><x-ui.button variant="danger" size="sm" data-loading-text="Rejecting…">Reject</x-ui.button></form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card">
            <div class="card-header"><h2 class="h3">Recently decided</h2></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Requested by</th><th class="num">Amount</th><th>Status</th><th>Updated</th></tr></thead>
                <tbody>
                    @forelse($recent as $refund)
                        <tr><td>{{ $refund->requester->name }}</td><td class="num font-semibold whitespace-nowrap">₱{{ number_format((float) $refund->amount, 2) }}</td><td><x-ui.status-badge :status="$refund->status" /></td><td class="whitespace-nowrap text-ink-secondary">{{ $refund->updated_at->format('M j, Y') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-ink-secondary">No decided refunds yet.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
    </div>
</x-layouts.app>
