@props(['onDark' => false, 'size' => 'md', 'href' => null])

@php
    $href ??= route('home');
    $mark = $size === 'lg' ? 'size-11 rounded-xl' : 'size-9 rounded-lg';
    $word = $size === 'lg' ? 'text-xl' : 'text-base';
@endphp

<a href="{{ $href }}" {{ $attributes->class(['inline-flex items-center gap-2.5 rounded-lg']) }} aria-label="Oncall Philippines home">
    <span class="flex {{ $mark }} shrink-0 items-center justify-center bg-navy-900 text-gold-400 shadow-card">
        <svg viewBox="0 0 24 24" class="{{ $size === 'lg' ? 'size-7' : 'size-5' }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 2.75c-2.5 2-5.4 3-8.25 3.1v6.4c0 5 3.5 8.9 8.25 10 4.75-1.1 8.25-5 8.25-10v-6.4c-2.85-.1-5.75-1.1-8.25-3.1Z" />
            <path d="m9 12.3 2 2 4-4.3" />
        </svg>
    </span>
    <span class="leading-none">
        <span class="block {{ $word }} font-bold tracking-tight {{ $onDark ? 'text-white' : 'text-navy-900' }}">Oncall</span>
        <span class="mt-0.5 block text-[11px] font-semibold tracking-[0.18em] uppercase {{ $onDark ? 'text-gold-300' : 'text-gold-700' }}">Philippines</span>
    </span>
</a>
