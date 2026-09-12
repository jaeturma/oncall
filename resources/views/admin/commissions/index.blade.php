<x-layouts.admin title="Sponsor commissions" description="Posted when a sponsored user is identity-verified. Approving releases the amount into the sponsor's wallet; reversing posts an opposing ledger entry.">
    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Sponsor</th><th>Sponsored user</th><th>Account type</th><th class="num">Amount</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse($commissions as $commission)
                        <tr>
                            <td class="font-semibold whitespace-nowrap">{{ $commission->sponsor->name }}</td>
                            <td class="whitespace-nowrap">{{ $commission->sponsoredUser->name }}</td>
                            <td class="whitespace-nowrap text-ink-secondary">{{ $commission->accountType?->name ?? '—' }}</td>
                            <td class="num font-semibold whitespace-nowrap">₱{{ number_format((float) $commission->amount, 2) }}</td>
                            <td><x-ui.status-badge :status="$commission->status" /></td>
                            <td>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    @can('approve', $commission)
                                        <form method="POST" action="{{ route('admin.commissions.update', $commission) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="approve"><x-ui.button variant="primary" size="sm" data-loading-text="Approving…">Approve</x-ui.button></form>
                                    @endcan
                                    @can('reverse', $commission)
                                        <form class="flex items-center gap-1.5" method="POST" action="{{ route('admin.commissions.update', $commission) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="reverse"><input class="input min-h-9 w-40 text-[13px]" name="reason" placeholder="Reason for reversal" aria-label="Reason for reversal" required><x-ui.button variant="danger" size="sm" data-loading-text="Reversing…">Reverse</x-ui.button></form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-ink-secondary">No commissions posted yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @if($commissions->hasPages())<div class="mt-5">{{ $commissions->links() }}</div>@endif
</x-layouts.admin>
