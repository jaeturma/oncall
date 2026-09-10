<x-layouts.admin title="Sponsor commissions">
    <div class="grid gap-6">
        @if(session('status'))<div class="rounded-xl border border-gold-200 bg-gold-50 p-4 font-semibold text-navy-900">{{ session('status') }}</div>@endif
        <div><h1 class="text-3xl font-black">Sponsor commissions</h1><p class="mt-2 text-slate-600">Posted when a sponsored user is identity-verified. Approving releases the amount into the sponsor's wallet; reversing posts an opposing ledger entry.</p></div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-6 py-3">Sponsor</th><th class="px-6 py-3">Sponsored user</th><th class="px-6 py-3">Account type</th><th class="px-6 py-3">Amount</th><th class="px-6 py-3">Status</th><th class="px-6 py-3">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($commissions as $commission)
                            <tr>
                                <td class="px-6 py-4 font-semibold">{{ $commission->sponsor->name }}</td>
                                <td class="px-6 py-4">{{ $commission->sponsoredUser->name }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $commission->accountType?->name ?? '—' }}</td>
                                <td class="px-6 py-4 font-bold">PHP {{ number_format((float) $commission->amount, 2) }}</td>
                                <td class="px-6 py-4">{{ str($commission->status->value)->title() }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @can('approve', $commission)
                                            <form method="POST" action="{{ route('admin.commissions.update', $commission) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="approve"><button class="rounded-lg bg-gold-400 px-3 py-1 text-xs font-bold text-navy-900">Approve</button></form>
                                        @endcan
                                        @can('reverse', $commission)
                                            <form method="POST" action="{{ route('admin.commissions.update', $commission) }}" class="flex gap-1">@csrf @method('PATCH')<input type="hidden" name="decision" value="reverse"><input class="w-32 rounded-lg border border-slate-300 px-2 py-1 text-xs" name="reason" placeholder="Reason" required><button class="rounded-lg border border-red-300 px-3 py-1 text-xs font-bold text-red-700">Reverse</button></form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-8 text-center text-slate-500" colspan="6">No commissions posted yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <div>{{ $commissions->links() }}</div>
    </div>
</x-layouts.admin>
