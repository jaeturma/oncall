@php
    $peso = fn ($amount): string => '₱'.number_format((float) $amount, 2);
@endphp

<x-layouts.app title="Receipt {{ $receipt['receipt_number'] ?? '' }}" eyebrow="Payment receipt">
    <div class="mx-auto max-w-xl">
        <section class="card card-pad">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="h3">{{ $receipt['receipt_number'] ?? 'Receipt pending' }}</h2>
                    <p class="mt-0.5 text-sm text-ink-secondary">{{ $receipt['service'] ?? 'Service' }} &middot; Job #{{ $receipt['job_id'] }}</p>
                </div>
                <x-ui.badge tone="neutral">{{ str($receipt['status'])->replace('_', ' ')->title() }}</x-ui.badge>
            </div>

            <dl class="mt-6 grid gap-3 text-sm">
                <div class="flex justify-between"><dt class="text-ink-muted">Customer</dt><dd class="text-ink">{{ $receipt['customer_name'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Provider</dt><dd class="text-ink">{{ $receipt['provider_name'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Payment method</dt><dd class="text-ink">{{ $receipt['payment_method'] ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Reference</dt><dd class="text-ink">{{ $receipt['payment_reference'] ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Confirmed</dt><dd class="text-ink">{{ $receipt['confirmed_at'] ? \Illuminate\Support\Carbon::parse($receipt['confirmed_at'])->format('M j, Y g:i A') : '—' }}</dd></div>
            </dl>

            <div class="mt-6 grid gap-2 border-t border-line pt-5 text-sm">
                <div class="flex justify-between"><dt class="text-ink-muted">Gross amount</dt><dd class="text-ink tabular-nums">{{ $peso($receipt['gross_amount']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Platform fee</dt><dd class="text-ink tabular-nums">{{ $peso($receipt['platform_fee']) }}</dd></div>
                <div class="flex justify-between font-semibold"><dt class="text-ink">Net amount</dt><dd class="text-ink tabular-nums">{{ $peso($receipt['net_amount']) }}</dd></div>
                @if((float) $receipt['refunded_amount'] > 0)
                    <div class="flex justify-between text-danger-700"><dt>Refunded</dt><dd class="tabular-nums">-{{ $peso($receipt['refunded_amount']) }}</dd></div>
                    <div class="flex justify-between font-semibold"><dt class="text-ink">Refundable remaining</dt><dd class="text-ink tabular-nums">{{ $peso($receipt['refundable_amount']) }}</dd></div>
                @endif
            </div>
        </section>
    </div>
</x-layouts.app>
