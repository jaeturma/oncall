@props(['title', 'heading' => null, 'intro' => null, 'wide' => false])

<!DOCTYPE html>
<html lang="en-PH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#01273a">
    <title>{{ $title }} · Oncall Philippines</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    {{ Vite::fonts() }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas text-ink">
    <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
        {{-- Brand / trust panel --}}
        <aside class="relative hidden overflow-hidden bg-navy-900 text-white lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div class="pointer-events-none absolute -top-32 -right-32 size-96 rounded-full bg-gold-400/10 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-40 -left-24 size-96 rounded-full bg-navy-300/10 blur-3xl" aria-hidden="true"></div>
            <x-ui.logo on-dark size="lg" class="relative" />
            <div class="relative max-w-md">
                <p class="eyebrow text-gold-300">Verified people, ready to help</p>
                <h2 class="mt-3 text-3xl font-bold text-white">A safer way to find local help in the Philippines.</h2>
                <ul class="mt-8 grid gap-5">
                    <li class="flex gap-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-gold-300"><x-ui.icon name="shield-check" class="size-5" /></span>
                        <div><p class="font-semibold">Verified providers</p><p class="mt-0.5 text-sm text-white/70">Identity and credentials are reviewed by Oncall staff before a badge is shown.</p></div>
                    </li>
                    <li class="flex gap-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-gold-300"><x-ui.icon name="lock-closed" class="size-5" /></span>
                        <div><p class="font-semibold">Contact details stay private</p><p class="mt-0.5 text-sm text-white/70">Phone numbers and emails are shared only after a booking is confirmed.</p></div>
                    </li>
                    <li class="flex gap-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-gold-300"><x-ui.icon name="clipboard" class="size-5" /></span>
                        <div><p class="font-semibold">Everything on record</p><p class="mt-0.5 text-sm text-white/70">Requests, agreements, and payments are recorded so Oncall can help if something goes wrong.</p></div>
                    </li>
                </ul>
            </div>
            <p class="relative text-xs text-white/50">&copy; {{ now()->year }} Oncall Philippines</p>
        </aside>

        {{-- Form panel --}}
        <div class="flex flex-col">
            <div class="flex h-16 items-center justify-between px-4 sm:px-8 lg:hidden">
                <x-ui.logo />
                <a class="btn btn-ghost btn-sm" href="{{ route('home') }}"><x-ui.icon name="arrow-left" class="size-4" />Home</a>
            </div>
            <div class="flex flex-1 items-start justify-center px-4 py-6 sm:px-8 sm:py-12 lg:items-center">
                <div class="w-full {{ $wide ? 'max-w-xl' : 'max-w-md' }}">
                    <a class="mb-8 hidden text-sm font-medium text-ink-muted hover:text-navy-900 lg:inline-flex lg:items-center lg:gap-1.5" href="{{ route('home') }}"><x-ui.icon name="arrow-left" class="size-4" />Back to Oncall</a>
                    @if($heading)
                        <h1 class="h1">{{ $heading }}</h1>
                        @if($intro)<p class="mt-2 text-ink-secondary">{{ $intro }}</p>@endif
                    @endif
                    <div class="mt-6">
                        <x-flash class="mb-5" />
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
