@props(['title' => 'Nothing here yet', 'message' => null, 'icon' => 'inbox', 'compact' => false])

<div {{ $attributes->class(['card flex flex-col items-center text-center', $compact ? 'px-6 py-8' : 'px-6 py-12 sm:py-16']) }}>
    <span class="flex size-14 items-center justify-center rounded-2xl bg-navy-50 text-navy-700"><x-ui.icon :name="$icon" class="size-7" /></span>
    <h3 class="mt-4 text-lg font-semibold text-ink">{{ $title }}</h3>
    @if($message)<p class="mt-1.5 max-w-md text-ink-secondary">{{ $message }}</p>@endif
    @if(trim($slot))<div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>@endif
</div>
