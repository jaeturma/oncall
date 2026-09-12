@props(['name' => null, 'initials' => null, 'size' => 'md', 'anonymous' => false, 'tone' => 'light'])

@php
    $letters = $initials ?? collect(explode(' ', trim((string) $name)))->filter()->take(2)->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $dimensions = match ($size) {
        'xs' => 'size-7 text-[11px]',
        'sm' => 'size-9 text-xs',
        'md' => 'size-12 text-sm',
        'lg' => 'size-16 text-lg',
        'xl' => 'size-20 text-2xl sm:size-24 sm:text-3xl',
        default => 'size-12 text-sm',
    };
    $palette = match ($tone) {
        'dark' => 'bg-white/10 text-gold-300 ring-1 ring-white/15',
        'accent' => 'bg-gold-400 text-navy-900',
        default => 'bg-navy-50 text-navy-800 ring-1 ring-navy-100',
    };
@endphp

<span {{ $attributes->class(['inline-flex shrink-0 items-center justify-center rounded-full font-semibold tracking-wide select-none', $dimensions, $palette]) }} aria-hidden="true">
    @if($anonymous || $letters === '')
        <x-ui.icon name="user" class="{{ $size === 'xl' ? 'size-10' : ($size === 'lg' ? 'size-7' : 'size-5') }} opacity-70" />
    @else
        {{ $letters }}
    @endif
</span>
