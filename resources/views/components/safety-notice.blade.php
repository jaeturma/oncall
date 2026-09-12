@props(['variant' => 'full'])

@if($variant === 'compact')
    <div {{ $attributes->class(['flex items-start gap-2.5 rounded-xl bg-gold-50 px-4 py-3 text-sm text-navy-900 ring-1 ring-gold-200']) }} role="note">
        <x-ui.icon name="shield-check" class="mt-0.5 size-4 text-gold-700" />
        <p><span class="font-semibold">Stay protected with Oncall.</span> Keep all communication, agreements, payments, and transactions within Oncall Philippines so we can help if something goes wrong.</p>
    </div>
@else
    <div {{ $attributes->class(['flex gap-4 rounded-2xl bg-gold-50 p-5 text-navy-900 ring-1 ring-gold-200 sm:p-6']) }} role="note">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-gold-400 text-navy-900"><x-ui.icon name="shield-check" class="size-6" /></span>
        <div class="min-w-0">
            <p class="text-base font-semibold">Stay protected with Oncall</p>
            <p class="mt-1 text-sm text-navy-800">Keep all communication, agreements, payments, and transactions within Oncall Philippines. Transactions arranged outside the platform cannot be fully monitored, verified, or protected by Oncall Philippines.</p>
            @if(trim($slot))<div class="mt-3 text-sm text-navy-800">{{ $slot }}</div>@endif
        </div>
    </div>
@endif
