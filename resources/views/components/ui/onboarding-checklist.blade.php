@props(['title' => 'Finish setting up your account', 'items'])

@php
    $items = collect($items);
    $total = $items->count();
    $done = $items->where('done', true)->count();
    $remaining = $items->firstWhere('done', false);
@endphp

@if($done < $total)
    <section class="card card-pad" aria-labelledby="onboarding-heading">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="onboarding-heading" class="h3">{{ $title }}</h2>
                <p class="mt-1 text-sm text-ink-secondary">{{ $done }} of {{ $total }} steps complete. A little more trust for the people you {{ $remaining['audience'] ?? 'work with' }}.</p>
            </div>
            @if($remaining)
                <x-ui.button :href="$remaining['href']" variant="primary" size="sm">{{ $remaining['cta'] ?? 'Continue' }}</x-ui.button>
            @endif
        </div>

        <div class="mt-4 h-2 overflow-hidden rounded-full bg-surface-muted" role="progressbar" aria-valuenow="{{ $done }}" aria-valuemin="0" aria-valuemax="{{ $total }}" aria-label="Onboarding progress">
            <div class="h-full rounded-full bg-gold-400 transition-all" style="width: {{ $total > 0 ? round($done / $total * 100) : 0 }}%"></div>
        </div>

        <ul class="mt-4 grid gap-2.5">
            @foreach($items as $item)
                <li class="flex items-start gap-3 text-sm">
                    <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full {{ $item['done'] ? 'bg-success-500 text-white' : 'border-2 border-line-strong' }}">
                        @if($item['done'])<x-ui.icon name="check" class="size-3" />@endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="{{ $item['done'] ? 'text-ink-muted line-through' : 'font-medium text-ink' }}">{{ $item['label'] }}</span>
                        @if(! $item['done'] && ($item['description'] ?? null))<span class="block text-ink-muted">{{ $item['description'] }}</span>@endif
                    </span>
                    @if(! $item['done'])
                        <a class="shrink-0 text-sm font-semibold text-navy-800 hover:underline" href="{{ $item['href'] }}">{{ $item['cta'] ?? 'Do this' }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
