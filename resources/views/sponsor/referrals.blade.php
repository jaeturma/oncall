<x-layouts.app title="Sponsored users">
    <div class="grid gap-6">
        <div><p class="font-bold uppercase tracking-widest text-navy-800">Sponsor</p><h1 class="text-3xl font-black">People you sponsored</h1><p class="mt-2 text-slate-600">Sponsorship is single level &mdash; you earn only from users you directly sponsored.</p></div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Sponsored users</p><p class="mt-1 text-2xl font-black">{{ $totals['count'] }}</p></div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Commission available</p><p class="mt-1 text-2xl font-black">PHP {{ number_format((float) $totals['available'], 2) }}</p></div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">Commission pending</p><p class="mt-1 text-2xl font-black text-slate-500">PHP {{ number_format((float) $totals['pending'], 2) }}</p></div>
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-6 py-3">User</th><th class="px-6 py-3">Account type</th><th class="px-6 py-3">Joined</th><th class="px-6 py-3">Identity</th><th class="px-6 py-3">Commission</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sponsored as $user)
                            @php($commission = $commissionByUser[$user->id] ?? null)
                            <tr>
                                <td class="px-6 py-4 font-semibold">{{ $user->name }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $user->accountType?->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $user->created_at->format('M j, Y') }}</td>
                                <td class="px-6 py-4">{{ str($user->identity_verification_status->value)->title() }}</td>
                                <td class="px-6 py-4">{{ $commission ? 'PHP '.number_format((float) $commission->amount, 2).' · '.str($commission->status->value)->title() : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-8 text-center text-slate-500" colspan="5">You have not sponsored anyone yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <div>{{ $sponsored->links() }}</div>
    </div>
</x-layouts.app>
