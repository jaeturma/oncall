<x-layouts.admin title="Payment reconciliation" description="Internal-consistency checks over the wallet ledger — Oncall has no external gateway to reconcile against, so these flag stale payments, stale attempts, duplicate references, and refund-cache mismatches. Run `php artisan payments:reconcile` to refresh this queue.">
    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Category</th><th>Description</th><th>Flagged</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse($flags as $flag)
                        <tr>
                            <td class="font-semibold whitespace-nowrap">{{ str($flag->category->value)->replace('_', ' ')->title() }}</td>
                            <td class="max-w-md">{{ $flag->description }}</td>
                            <td class="whitespace-nowrap text-ink-secondary">{{ $flag->created_at->diffForHumans() }}</td>
                            <td>
                                <form class="flex items-center gap-1.5 justify-end" method="POST" action="{{ route('admin.finance.reconciliation.resolve', $flag) }}">
                                    @csrf @method('PATCH')
                                    <input class="input min-h-9 w-56 text-[13px]" name="resolution_notes" placeholder="Resolution notes" aria-label="Resolution notes" required>
                                    <x-ui.button variant="primary" size="sm" data-loading-text="Resolving…">Resolve</x-ui.button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-10 text-center text-ink-secondary">No open reconciliation flags.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @if($flags->hasPages())<div class="mt-5">{{ $flags->links() }}</div>@endif
</x-layouts.admin>
