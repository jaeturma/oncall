@php
    $categoryIcons = [
        'household-help' => 'home',
        'skilled-trades' => 'wrench',
        'transport-automotive' => 'truck',
        'technical-repair' => 'computer',
        'professional-services' => 'academic-cap',
    ];
@endphp

<x-layouts.public>
    {{-- Hero + Service Finder --}}
    <section class="relative overflow-hidden bg-navy-900 text-white" id="find-help">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -top-40 right-[-10%] size-[34rem] rounded-full bg-gold-400/10 blur-3xl"></div>
            <div class="absolute -bottom-48 left-[-10%] size-[30rem] rounded-full bg-navy-300/10 blur-3xl"></div>
        </div>
        <div class="container-x relative py-14 sm:py-20 lg:py-24">
            <div class="mx-auto max-w-3xl text-center">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-semibold tracking-wide text-gold-200 ring-1 ring-white/10">
                    <x-ui.icon name="shield-check" class="size-4 text-gold-300" />
                    Verified people, ready to help
                </p>
                <h1 class="display mt-5 text-white">Find trusted help near you.</h1>
                <p class="mx-auto mt-4 max-w-2xl text-base text-white/75 sm:text-lg">Connect with verified local service providers for urgent, household, skilled, and professional needs — and keep every request, agreement, and payment safely on Oncall.</p>
            </div>

            <div class="mx-auto mt-8 max-w-4xl sm:mt-10">
                <x-search-form :categories="$categories" :provinces="$provinces" />
                <ul class="mt-5 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-white/70">
                    <li class="flex items-center gap-1.5"><x-ui.icon name="check-circle" class="size-4 text-gold-300" />Identity-verified providers</li>
                    <li class="flex items-center gap-1.5"><x-ui.icon name="lock-closed" class="size-4 text-gold-300" />Contact details stay private until you book</li>
                    <li class="flex items-center gap-1.5"><x-ui.icon name="clipboard" class="size-4 text-gold-300" />Every booking on record</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Popular services --}}
    <section class="section" aria-labelledby="popular-heading">
        <div class="container-x">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <x-ui.section-heading id="popular-heading" eyebrow="Popular services" title="What do you need help with?" description="Pick a category to start, or choose a specific service in the finder above." />
            </div>
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach($categories as $category)
                    <li>
                        <button type="button" class="card card-interactive flex w-full items-start gap-4 p-5 text-left" data-pick-help="category:{{ $category->id }}">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-navy-900 text-gold-300"><x-ui.icon :name="$categoryIcons[$category->slug] ?? 'sparkles'" class="size-6" /></span>
                            <span class="min-w-0">
                                <span class="block text-base font-semibold text-ink">{{ $category->name }}</span>
                                <span class="mt-1 block text-sm text-ink-secondary">{{ $category->services->take(4)->pluck('name')->join(', ') }}{{ $category->services->count() > 4 ? ', and more' : '' }}</span>
                                <span class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-navy-800">Find {{ str($category->name)->lower() }} <x-ui.icon name="arrow-right" class="size-4" /></span>
                            </span>
                        </button>
                    </li>
                @endforeach
                <li>
                    <a class="card card-interactive flex h-full items-start gap-4 border-dashed p-5 ring-line-strong" href="{{ route('register', ['role' => 'provider']) }}">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-gold-100 text-gold-800"><x-ui.icon name="briefcase" class="size-6" /></span>
                        <span class="min-w-0">
                            <span class="block text-base font-semibold text-ink">Offer your services</span>
                            <span class="mt-1 block text-sm text-ink-secondary">Skilled workers and licensed professionals can join, get verified, and receive requests from nearby customers.</span>
                            <span class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-navy-800">Become a provider <x-ui.icon name="arrow-right" class="size-4" /></span>
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </section>

    {{-- How it works --}}
    <section class="border-y border-line bg-surface" id="how-it-works" aria-labelledby="how-heading">
        <div class="container-x section">
            <x-ui.section-heading id="how-heading" eyebrow="How Oncall works" title="Help in four simple steps" description="Made for people who just want the job done safely — no technical know-how needed." align="center" />
            <ol class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['icon' => 'search', 'title' => 'Find a service', 'text' => 'Choose what you need and your province. Available providers appear first.'],
                    ['icon' => 'shield-check', 'title' => 'Choose a verified provider', 'text' => 'Compare ratings, completed services, and verification badges reviewed by Oncall staff.'],
                    ['icon' => 'chat', 'title' => 'Request help', 'text' => 'Send a request with your schedule and budget. Contact details are shared only after the provider accepts.'],
                    ['icon' => 'clipboard', 'title' => 'Keep it on Oncall', 'text' => 'Messages, agreements, and payment confirmations stay on record, so we can step in if something goes wrong.'],
                ] as $index => $step)
                    <li class="relative flex gap-4 lg:block">
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-navy-900 text-gold-300"><x-ui.icon :name="$step['icon']" class="size-5" /></span>
                            <span class="hidden text-sm font-bold text-ink-muted lg:inline">Step {{ $index + 1 }}</span>
                        </div>
                        <div class="lg:mt-4">
                            <p class="text-base font-semibold text-ink"><span class="lg:hidden">{{ $index + 1 }}. </span>{{ $step['title'] }}</p>
                            <p class="mt-1 text-sm text-ink-secondary">{{ $step['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Trust & safety --}}
    <section class="section" id="safety" aria-labelledby="safety-heading">
        <div class="container-x">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] lg:items-center">
                <div>
                    <x-ui.section-heading id="safety-heading" eyebrow="Safety first" title="Safer than finding workers on social media or through strangers" description="Oncall is built around one idea: you should know who is coming to help, and there should be a record if anything goes wrong." />
                    <div class="mt-6 grid gap-3">
                        <x-safety-notice variant="compact" />
                        <x-emergency-notice />
                    </div>
                </div>
                <ul class="grid gap-4 sm:grid-cols-2">
                    @foreach([
                        ['icon' => 'shield-check', 'title' => 'Verified providers', 'text' => 'Government IDs and professional credentials are reviewed by Oncall staff before a verification badge is shown.'],
                        ['icon' => 'lock-closed', 'title' => 'Private until you book', 'text' => 'Guests see anonymised profiles. Phone numbers and emails are only shared between you and your provider after a booking is confirmed.'],
                        ['icon' => 'clipboard', 'title' => 'Everything on record', 'text' => 'Requests, messages, agreements, payment confirmations, and safety reports are kept with each booking.'],
                        ['icon' => 'star', 'title' => 'Reviews from real bookings', 'text' => 'Only people who completed a service on Oncall can leave a review, and accounts that break the rules face enforcement.'],
                    ] as $item)
                        <li class="card card-pad">
                            <span class="flex size-10 items-center justify-center rounded-lg bg-gold-100 text-gold-800"><x-ui.icon :name="$item['icon']" class="size-5" /></span>
                            <p class="mt-4 font-semibold text-ink">{{ $item['title'] }}</p>
                            <p class="mt-1 text-sm text-ink-secondary">{{ $item['text'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- Provider call to action --}}
    <section class="container-x pb-16">
        <div class="panel-dark relative overflow-hidden px-6 py-10 sm:px-10 sm:py-12">
            <div class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-gold-400/15 blur-3xl" aria-hidden="true"></div>
            <div class="relative grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                <div class="max-w-2xl">
                    <p class="eyebrow text-gold-300">For skilled workers and professionals</p>
                    <h2 class="mt-2 text-2xl font-bold text-white sm:text-3xl">Get verified. Get requests. Get paid on record.</h2>
                    <p class="mt-3 text-white/75">Create a provider profile, submit your ID and credentials for review, and start receiving requests from customers in your area — with every agreement recorded on Oncall.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button :href="route('register', ['role' => 'provider'])" variant="primary" size="lg">Become a provider</x-ui.button>
                    <x-ui.button href="#how-it-works" variant="on-dark" size="lg">See how it works</x-ui.button>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
