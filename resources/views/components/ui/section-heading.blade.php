@props(['title', 'eyebrow' => null, 'description' => null, 'align' => 'left', 'onDark' => false])

<div {{ $attributes->class(['max-w-2xl', 'mx-auto text-center' => $align === 'center']) }}>
    @if($eyebrow)<p class="eyebrow {{ $onDark ? 'text-gold-300' : 'text-gold-700' }}">{{ $eyebrow }}</p>@endif
    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl {{ $onDark ? 'text-white' : 'text-ink' }}">{{ $title }}</h2>
    @if($description)<p class="mt-3 text-base {{ $onDark ? 'text-white/75' : 'text-ink-secondary' }} sm:text-lg">{{ $description }}</p>@endif
</div>
