@props([
    'variant' => 'primary',
    'size' => null,
    'href' => null,
    'type' => 'submit',
    'icon' => null,
    'iconRight' => null,
    'block' => false,
])

@php
    $classes = [
        'btn',
        'btn-'.$variant,
        $size ? 'btn-'.$size : null,
        $block ? 'btn-block' : null,
    ];
    $iconClass = $size === 'sm' ? 'size-4' : 'size-5';
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if($icon)<x-ui.icon :name="$icon" class="{{ $iconClass }}" />@endif
        <span>{{ $slot }}</span>
        @if($iconRight)<x-ui.icon :name="$iconRight" class="{{ $iconClass }}" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if($icon)<x-ui.icon :name="$icon" class="{{ $iconClass }}" />@endif
        <span>{{ $slot }}</span>
        @if($iconRight)<x-ui.icon :name="$iconRight" class="{{ $iconClass }}" />@endif
    </button>
@endif
