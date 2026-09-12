@props(['value' => null, 'count' => null, 'countLabel' => 'review', 'size' => 'sm', 'onDark' => false])

@php
    $numeric = $value !== null ? (float) $value : null;
    $textSize = $size === 'md' ? 'text-base' : 'text-sm';
    $iconSize = $size === 'md' ? 'size-5' : 'size-4';
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 font-medium', $textSize, $onDark ? 'text-white' : 'text-ink']) }}>
    <x-ui.icon name="star" :solid="$numeric !== null" class="{{ $iconSize }} {{ $numeric !== null ? 'text-gold-500' : ($onDark ? 'text-white/50' : 'text-slate-300') }}" />
    @if($numeric !== null)
        <span class="font-semibold tabular-nums">{{ number_format($numeric, 1) }}</span>
        <span class="sr-only">out of 5</span>
    @else
        <span class="{{ $onDark ? 'text-white/70' : 'text-ink-muted' }}">New</span>
    @endif
    @if($count !== null && (int) $count > 0)
        <span class="{{ $onDark ? 'text-white/70' : 'text-ink-muted' }}">({{ $count }} {{ str($countLabel)->plural($count) }})</span>
    @endif
</span>
