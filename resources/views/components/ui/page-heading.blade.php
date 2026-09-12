@props(['title', 'eyebrow' => null, 'description' => null, 'level' => 'h1'])

<div {{ $attributes->class(['flex flex-wrap items-end justify-between gap-x-6 gap-y-4']) }}>
    <div class="min-w-0 max-w-3xl">
        @if($eyebrow)<p class="eyebrow text-navy-700">{{ $eyebrow }}</p>@endif
        <{{ $level }} class="{{ $level === 'h1' ? 'h1' : 'h2' }} {{ $eyebrow ? 'mt-1' : '' }}">{{ $title }}</{{ $level }}>
        @if($description)<p class="mt-2 text-ink-secondary">{{ $description }}</p>@endif
        @if(trim($slot))<div class="mt-2 text-ink-secondary">{{ $slot }}</div>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
</div>
