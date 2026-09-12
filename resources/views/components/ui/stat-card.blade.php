@props(['label', 'value', 'hint' => null, 'icon' => null, 'href' => null, 'tone' => 'neutral'])

@php
    $iconTone = match ($tone) {
        'accent' => 'bg-gold-100 text-gold-800',
        'success' => 'bg-success-50 text-success-700',
        'warning' => 'bg-warning-50 text-warning-700',
        'danger' => 'bg-danger-50 text-danger-700',
        'brand' => 'bg-navy-900 text-gold-300',
        default => 'bg-navy-50 text-navy-800',
    };
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->class(['card card-pad flex items-start gap-4', 'card-interactive' => $href]) }}>
    @if($icon)
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $iconTone }}"><x-ui.icon :name="$icon" class="size-5" /></span>
    @endif
    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-ink-muted">{{ $label }}</p>
        <p class="mt-1 text-2xl font-bold tracking-tight text-ink tabular-nums sm:text-3xl">{{ $value }}</p>
        @if($hint)<p class="mt-1 text-sm text-ink-secondary">{{ $hint }}</p>@endif
    </div>
    @if($href)<x-ui.icon name="chevron-right" class="mt-1 size-4 text-ink-muted" />@endif
</{{ $tag }}>
