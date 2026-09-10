<x-layouts.app title="Job payments">
    <div class="grid gap-6">
        <x-flash />
        <div><p class="font-bold uppercase tracking-widest text-navy-800">{{ str(auth()->user()->role->value)->title() }}</p><h1 class="text-3xl font-black">Job payments to release</h1><p class="mt-2 text-slate-600">A Service Finder has confirmed payment. Releasing moves the net earning into the provider's wallet; only release once Oncall holds the funds.</p></div>

        <div class="grid gap-4">
            @forelse($awaitingRelease as $payment)
                <article class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-black">{{ $payment->provider->name }} &mdash; {{ $payment->job->serviceRequest->title }}</p>
                            <p class="mt-1 text-sm text-slate-600">Gross PHP {{ number_format((float) $payment->gross_amount, 2) }} &middot; fee PHP {{ number_format((float) $payment->platform_fee, 2) }} &middot; <span class="font-bold text-navy-900">net PHP {{ number_format((float) $payment->net_amount, 2) }}</span></p>
                            <p class="mt-1 text-xs text-slate-500">Finder confirmed {{ $payment->confirmed_at?->diffForHumans() }} via {{ $payment->payment_method }} ({{ $payment->payment_reference }})</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ str($payment->status->value)->title() }}</span>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-200 pt-5">
                        @can('release', $payment)
                            <form method="POST" action="{{ route('staff.job-payments.update', $payment) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="release"><button class="rounded-lg bg-gold-400 px-4 py-2 text-sm font-bold text-navy-900 hover:bg-gold-500">Release to wallet</button></form>
                        @endcan
                        @can('reverse', $payment)
                            <form class="flex gap-1" method="POST" action="{{ route('staff.job-payments.update', $payment) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="reverse"><input class="w-40 rounded-lg border border-slate-300 px-2 py-1 text-sm" name="reason" placeholder="Reason" required><button class="rounded-lg border border-red-300 px-4 py-2 text-sm font-bold text-red-700">Reverse</button></form>
                        @endcan
                    </div>
                </article>
            @empty
                <div class="rounded-2xl bg-white p-8 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">Nothing awaiting release.</div>
            @endforelse
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-200 p-6"><h2 class="text-xl font-black">Recently closed</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-6 py-3">Provider</th><th class="px-6 py-3">Net</th><th class="px-6 py-3">Status</th><th class="px-6 py-3">Updated</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recent as $payment)
                            <tr><td class="px-6 py-4">{{ $payment->provider->name }}</td><td class="px-6 py-4 font-bold">PHP {{ number_format((float) $payment->net_amount, 2) }}</td><td class="px-6 py-4">{{ str($payment->status->value)->title() }}</td><td class="px-6 py-4">{{ $payment->updated_at->format('M j, Y') }}</td></tr>
                        @empty
                            <tr><td class="px-6 py-8 text-center text-slate-500" colspan="4">No released or reversed payments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.app>
