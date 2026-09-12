<x-layouts.admin title="Disputes" description="While a dispute is open, the job's payment cannot be released. Resolving it releases, reverses, or adjusts the payment.">
    <div class="grid gap-6">
        <section>
            <h2 class="h3 mb-3">Open</h2>
            @if($open->isEmpty())
                <x-empty-state compact icon="scale" title="No open disputes" message="New disputes raised from bookings will appear here." />
            @else
                <div class="card">
                    <ul class="stack-list">
                        @foreach($open as $dispute)
                            <li>
                                <a class="flex flex-col gap-3 px-5 py-4 hover:bg-surface-muted sm:flex-row sm:items-center sm:justify-between sm:px-6" href="{{ route('admin.disputes.show', $dispute) }}">
                                    <span class="min-w-0">
                                        <span class="block font-semibold text-ink">{{ $dispute->job->serviceRequest->title }}</span>
                                        <span class="mt-0.5 block text-sm text-ink-muted">{{ $dispute->raisedBy->name }} vs {{ $dispute->againstUser->name }} &middot; {{ str($dispute->category->value)->replace('_', ' ')->lower()->ucfirst() }} &middot; opened {{ $dispute->created_at->diffForHumans() }}</span>
                                    </span>
                                    <span class="flex items-center gap-3"><x-ui.status-badge :status="$dispute->status" /><x-ui.icon name="chevron-right" class="size-4 text-ink-muted" /></span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <section class="card">
            <div class="card-header"><h2 class="h3">Recently resolved</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Parties</th><th>Outcome</th><th class="num">Refund</th><th>Resolved</th></tr></thead>
                    <tbody>
                        @forelse($closed as $dispute)
                            <tr>
                                <td>{{ $dispute->raisedBy->name }} vs {{ $dispute->againstUser->name }}</td>
                                <td><x-ui.status-badge :status="$dispute->status" /></td>
                                <td class="num whitespace-nowrap">{{ $dispute->refund_amount ? '₱'.number_format((float) $dispute->refund_amount, 2) : '—' }}</td>
                                <td class="whitespace-nowrap text-ink-secondary">{{ $dispute->resolved_at?->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-ink-secondary">No resolved disputes yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
