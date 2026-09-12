@props(['variant' => 'full'])

@if($variant === 'compact')
    <p {{ $attributes->class(['flex items-start gap-2 text-sm text-ink-secondary']) }} role="note">
        <x-ui.icon name="exclamation-triangle" class="mt-0.5 size-4 shrink-0 text-danger-600" />
        <span><span class="font-semibold text-ink">Oncall is not an emergency service.</span> For any life-threatening emergency, call <strong>911</strong> or your local hotline.</span>
    </p>
@else
    <div {{ $attributes->class(['flex gap-3 rounded-xl border border-danger-100 bg-danger-50 p-4 text-sm text-danger-800']) }} role="note">
        <x-ui.icon name="exclamation-triangle" class="mt-0.5 size-5 shrink-0 text-danger-600" />
        <div>
            <p class="font-semibold">Oncall is not an emergency service</p>
            <p class="mt-0.5">For fire, crime, medical, or any life-threatening emergency, call <strong>911</strong> nationwide or your local emergency hotline right away. Oncall connects you with everyday local help and cannot dispatch emergency responders.</p>
        </div>
    </div>
@endif
