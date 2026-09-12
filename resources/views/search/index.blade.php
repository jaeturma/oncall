@php
    [$helpType, $helpId] = explode(':', $filters['help'], 2);
    $helpLabel = $helpType === 'service'
        ? $services->firstWhere('id', (int) $helpId)?->name
        : $categories->firstWhere('id', (int) $helpId)?->name;
    if (isset($filters['service_id'])) {
        $helpLabel = $services->firstWhere('id', (int) $filters['service_id'])?->name ?? $helpLabel;
    }
    $helpLabel ??= 'Providers';
    $placeLabel = isset($filters['municipality_id'])
        ? ($municipalities->firstWhere('id', (int) $filters['municipality_id'])?->name)
        : null;
    $placeLabel ??= $provinces->firstWhere('id', (int) $filters['province_id'])?->name ?? 'your area';
    $total = $providers->total();
    $activeRefinements = collect([$filters['municipality_id'] ?? null, $filters['service_id'] ?? null, $filters['available_only'] ?? null, $filters['min_rating'] ?? null, ($filters['sort'] ?? 'recommended') !== 'recommended' ? $filters['sort'] : null])->filter()->count();
    $pluralHelp = $helpType === 'service' || isset($filters['service_id']) ? str($helpLabel)->plural() : $helpLabel;
@endphp

<x-layouts.public :title="$pluralHelp.' in '.$placeLabel">
    <section class="border-b border-line bg-surface">
        <div class="container-x py-6 sm:py-8">
            <nav class="text-sm text-ink-muted" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-1.5">
                    <li><a class="hover:text-navy-900 hover:underline" href="{{ route('home') }}">Home</a></li>
                    <li aria-hidden="true"><x-ui.icon name="chevron-right" class="size-3.5" /></li>
                    <li aria-current="page" class="text-ink">Find help</li>
                </ol>
            </nav>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="h1">{{ $pluralHelp }} in {{ $placeLabel }}</h1>
                    <p class="mt-1 text-ink-secondary"><span class="font-semibold text-ink">{{ $total }} {{ str('provider')->plural($total) }} found</span> &middot; available providers first, then nearest. Distance is approximate, based on municipality location.</p>
                </div>
                <button type="button" class="btn btn-secondary lg:hidden" data-drawer-open="filter-drawer" aria-controls="filter-drawer" aria-expanded="false">
                    <x-ui.icon name="funnel" class="size-4" />
                    Filters
                    @if($activeRefinements > 0)<span class="badge badge-accent">{{ $activeRefinements }}</span>@endif
                </button>
            </div>
        </div>
    </section>

    <div class="container-x grid gap-8 py-8 lg:grid-cols-[19rem_minmax(0,1fr)] lg:py-10">
        {{-- Desktop filter sidebar --}}
        <aside class="hidden self-start lg:sticky lg:top-24 lg:block">
            <div class="card card-pad">
                <h2 class="h3">Refine results</h2>
                <x-search-form class="mt-4" :categories="$categories" :provinces="$provinces" :services="$services" :municipalities="$municipalities" :filters="$filters" refine />
            </div>
            <x-safety-notice variant="compact" class="mt-4" />
        </aside>

        {{-- Mobile filter drawer --}}
        <div id="filter-drawer" class="fixed inset-0 z-50 lg:hidden" data-drawer="mobile" hidden role="dialog" aria-modal="true" aria-label="Filter results">
            <div class="absolute inset-0 bg-navy-950/60" data-drawer-close></div>
            <div class="absolute inset-x-0 bottom-0 max-h-[90dvh] overflow-y-auto rounded-t-2xl bg-surface p-5 shadow-pop sm:inset-y-0 sm:left-auto sm:max-h-none sm:w-96 sm:rounded-none">
                <div class="flex items-center justify-between">
                    <h2 class="h3">Refine results</h2>
                    <button type="button" class="inline-flex size-11 items-center justify-center rounded-lg hover:bg-navy-50" data-drawer-close aria-label="Close filters"><x-ui.icon name="x-mark" class="size-6" /></button>
                </div>
                <x-search-form class="mt-4" :categories="$categories" :provinces="$provinces" :services="$services" :municipalities="$municipalities" :filters="$filters" refine id-prefix="m_" />
            </div>
        </div>

        <section aria-label="Search results">
            @guest
                <x-ui.alert tone="accent" icon="lock-closed" class="mb-5">
                    You're browsing as a guest. Provider names and contact details stay hidden until you <a class="font-semibold underline" href="{{ route('login') }}">sign in</a> or <a class="font-semibold underline" href="{{ route('register') }}">create a free account</a>.
                </x-ui.alert>
            @elseif(! $canRevealIdentity)
                <x-ui.alert tone="warning" class="mb-5">
                    Provider names and requests unlock once your identity is verified. <a class="font-semibold underline" href="{{ route('verification.index') }}">Complete verification</a>.
                </x-ui.alert>
            @endguest

            <div class="grid gap-4 xl:grid-cols-2">
                @forelse($providers as $provider)
                    <x-provider-card :profile="$provider" :reveal="$canRevealIdentity" />
                @empty
                    <x-empty-state class="xl:col-span-2" icon="search" title="No providers found" message="We couldn't find providers matching those filters. Try a broader service, remove the municipality filter, or search a nearby province.">
                        @if($activeRefinements > 0)
                            <x-ui.button :href="route('providers.search', ['help' => $filters['help'], 'province_id' => $filters['province_id']])" variant="dark">Clear filters</x-ui.button>
                        @endif
                        <x-ui.button :href="route('home').'#find-help'" variant="secondary">Change location</x-ui.button>
                    </x-empty-state>
                @endforelse
            </div>

            @if($providers->hasPages())<div class="mt-8">{{ $providers->links() }}</div>@endif

            <div class="mt-8 grid gap-3 lg:hidden">
                <x-safety-notice variant="compact" />
            </div>
        </section>
    </div>
</x-layouts.public>
