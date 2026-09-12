@props(['tone' => 'info', 'title' => null, 'icon' => null, 'role' => null])

@php
    $styles = match ($tone) {
        'success' => ['box' => 'border-success-100 bg-success-50 text-success-800', 'icon' => 'check-circle', 'iconColor' => 'text-success-600'],
        'warning' => ['box' => 'border-warning-100 bg-warning-50 text-warning-800', 'icon' => 'exclamation-triangle', 'iconColor' => 'text-warning-600'],
        'danger' => ['box' => 'border-danger-100 bg-danger-50 text-danger-800', 'icon' => 'exclamation-triangle', 'iconColor' => 'text-danger-600'],
        'accent' => ['box' => 'border-gold-200 bg-gold-50 text-navy-900', 'icon' => 'shield-check', 'iconColor' => 'text-gold-700'],
        default => ['box' => 'border-info-100 bg-info-50 text-info-800', 'icon' => 'information-circle', 'iconColor' => 'text-info-600'],
    };
    $resolvedRole = $role ?? ($tone === 'danger' ? 'alert' : 'status');
@endphp

<div {{ $attributes->class(['flex gap-3 rounded-xl border p-4 text-sm', $styles['box']]) }} role="{{ $resolvedRole }}">
    <x-ui.icon :name="$icon ?? $styles['icon']" class="mt-0.5 size-5 {{ $styles['iconColor'] }}" />
    <div class="min-w-0 flex-1">
        @if($title)<p class="font-semibold">{{ $title }}</p>@endif
        <div class="{{ $title ? 'mt-0.5' : '' }}">{{ $slot }}</div>
    </div>
</div>
