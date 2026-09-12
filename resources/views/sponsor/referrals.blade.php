<x-layouts.app title="Sponsored users" eyebrow="Sponsorship" description="People who registered with your email as their sponsor. Sponsorship is single level — you earn only from users you directly sponsored, once their identity is verified.">
    <div class="grid gap-6">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.stat-card label="Sponsored users" :value="$totals['count']" icon="users" />
            <x-ui.stat-card label="Commission available" :value="'₱'.number_format((float) $totals['available'], 2)" hint="In your wallet" icon="wallet" tone="accent" :href="route('wallet.index')" />
            <x-ui.stat-card label="Commission pending" :value="'₱'.number_format((float) $totals['pending'], 2)" hint="Awaiting Oncall approval" icon="clock" />
        </div>

        <section class="card">
            @if($sponsored->isEmpty())
                <div class="px-5 py-10 text-center sm:px-6">
                    <p class="font-medium text-ink">No sponsored users yet</p>
                    <p class="mt-1 text-sm text-ink-secondary">You have not sponsored anyone yet. Users who register through your sponsorship will appear here. Share your account email with people you refer; they enter it as their sponsor when signing up.</p>
                </div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>User</th><th>Account type</th><th>Joined</th><th>Identity</th><th>Commission</th></tr></thead>
                        <tbody>
                            @foreach($sponsored as $user)
                                @php($commission = $commissionByUser[$user->id] ?? null)
                                <tr>
                                    <td class="font-semibold whitespace-nowrap">{{ $user->name }}</td>
                                    <td class="text-ink-secondary">{{ $user->accountType?->name ?? '—' }}</td>
                                    <td class="whitespace-nowrap">{{ $user->created_at->format('M j, Y') }}</td>
                                    <td><x-ui.status-badge :status="$user->identity_verification_status" /></td>
                                    <td class="whitespace-nowrap">@if($commission)<span class="font-semibold tabular-nums">₱{{ number_format((float) $commission->amount, 2) }}</span> <x-ui.status-badge :status="$commission->status" class="ml-1" />@else<span class="text-ink-muted">—</span>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
        @if($sponsored->hasPages())<div>{{ $sponsored->links() }}</div>@endif
    </div>
</x-layouts.app>
