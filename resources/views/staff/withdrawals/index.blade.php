<x-layouts.app title="Withdrawal queue" :eyebrow="str($actingRole->value)->lower()->ucfirst()" description="Requested → Accounting review → Budget approval → Cashier disbursement. You can only act on the step assigned to your role." wide>
    <div class="grid gap-6">
        <section>
            <h2 class="h3 mb-3">Open requests</h2>
            @if($open->isEmpty())
                <x-empty-state compact icon="banknotes" title="Nothing in the queue" message="New withdrawal requests will appear here as they move through each step." />
            @else
                <div class="grid gap-4">
                    @foreach($open as $withdrawal)
                        <article class="card card-pad">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <x-ui.avatar :name="$withdrawal->user->name" size="md" />
                                    <div>
                                        <p class="text-lg font-bold text-ink tabular-nums">₱{{ number_format((float) $withdrawal->amount, 2)}}</p>
                                        <p class="text-sm text-ink">{{ $withdrawal->user->name }}</p>
                                        <p class="text-sm text-ink-secondary">{{ $withdrawal->payout_method }} &middot; {{ $withdrawal->payout_reference }}</p>
                                        <p class="mt-0.5 text-xs text-ink-muted">Requested {{ $withdrawal->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <x-ui.status-badge :status="$withdrawal->status" />
                            </div>
                            @if($withdrawal->notes)<p class="mt-3 rounded-lg bg-surface-muted p-3 text-sm text-ink-secondary"><span class="font-medium text-ink">Notes:</span> {{ $withdrawal->notes }}</p>@endif

                            @can('review', $withdrawal)
                                <form class="mt-5 grid gap-3 border-t border-line pt-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end" method="POST" action="{{ route('staff.withdrawals.update', $withdrawal) }}">
                                    @csrf @method('PATCH')
                                    <x-form.input name="notes" label="Notes" maxlength="1000" placeholder="Required when returning or rejecting" />
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.button variant="primary" name="decision" value="approve" data-loading-text="Saving…">{{ $withdrawal->status === App\Enums\WithdrawalStatus::ForDisbursement ? 'Mark disbursed' : 'Approve' }}</x-ui.button>
                                        <x-ui.button variant="secondary" name="decision" value="return" data-loading-text="Saving…">Return</x-ui.button>
                                        <x-ui.button variant="danger" name="decision" value="reject" data-loading-text="Saving…">Reject</x-ui.button>
                                    </div>
                                </form>
                            @else
                                <p class="mt-4 flex items-center gap-1.5 text-sm text-ink-muted"><x-ui.icon name="clock" class="size-4" />Waiting on the {{ str($withdrawal->status->actingRole()?->value)->lower()->ucfirst() }} step.</p>
                            @endcan
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card">
            <div class="card-header"><h2 class="h3">Recently closed</h2></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>User</th><th class="num">Amount</th><th>Status</th><th>Updated</th></tr></thead>
                <tbody>
                    @forelse($recent as $withdrawal)
                        <tr><td>{{ $withdrawal->user->name }}</td><td class="num font-semibold whitespace-nowrap">₱{{ number_format((float) $withdrawal->amount, 2) }}</td><td><x-ui.status-badge :status="$withdrawal->status" /></td><td class="whitespace-nowrap text-ink-secondary">{{ $withdrawal->updated_at->format('M j, Y') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-ink-secondary">No closed withdrawals yet.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
    </div>
</x-layouts.app>
