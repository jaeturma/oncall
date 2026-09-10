<x-layouts.admin title="Disputes">
    <div class="grid gap-6">
        @if(session('status'))<div class="rounded-xl border border-gold-200 bg-gold-50 p-4 font-semibold text-navy-900">{{ session('status') }}</div>@endif
        <div><h1 class="text-3xl font-black">Disputes</h1><p class="mt-2 text-slate-600">While a dispute is open the job's payment cannot be released. Resolving it releases, reverses or adjusts the payment.</p></div>

        <div class="grid gap-4">
            @forelse($open as $dispute)
                <a class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:ring-gold-400" href="{{ route('admin.disputes.show', $dispute) }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-black">{{ $dispute->job->serviceRequest->title }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $dispute->raisedBy->name }} vs {{ $dispute->againstUser->name }} &middot; {{ str($dispute->category->value)->replace('_', ' ')->title() }}</p>
                            <p class="mt-1 text-xs text-slate-500">Opened {{ $dispute->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ str($dispute->status->value)->replace('_', ' ')->title() }}</span>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl bg-white p-8 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">No open disputes.</div>
            @endforelse
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-200 p-6"><h2 class="text-xl font-black">Recently resolved</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-6 py-3">Parties</th><th class="px-6 py-3">Outcome</th><th class="px-6 py-3">Refund</th><th class="px-6 py-3">Resolved</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($closed as $dispute)
                            <tr>
                                <td class="px-6 py-4">{{ $dispute->raisedBy->name }} vs {{ $dispute->againstUser->name }}</td>
                                <td class="px-6 py-4">{{ str($dispute->status->value)->replace('_', ' ')->title() }}</td>
                                <td class="px-6 py-4">{{ $dispute->refund_amount ? 'PHP '.number_format((float) $dispute->refund_amount, 2) : '—' }}</td>
                                <td class="px-6 py-4">{{ $dispute->resolved_at?->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-8 text-center text-slate-500" colspan="4">No resolved disputes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
