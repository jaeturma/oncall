@php
    $primaryService = $profile->providerServices->first()?->service?->name ?? 'Service Provider';
    $displayName = $reveal ? $profile->user->name : 'Verified '.$primaryService.' #'.str_pad((string) $profile->id, 4, '0', STR_PAD_LEFT);
@endphp

<x-layouts.public :title="$displayName">
    <section class="border-b border-slate-200 bg-navy-900 px-6 py-12 text-white">
        <div class="mx-auto max-w-4xl">
            <a class="text-sm font-bold uppercase tracking-widest text-gold-300 hover:underline" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('providers.search') }}">&larr; Back to results</a>
            <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black sm:text-4xl">{{ $displayName }}</h1>
                    <p class="mt-2 text-slate-300">{{ $primaryService }} &middot; Serving {{ $profile->municipality->name }}, {{ $profile->province->name }}</p>
                </div>
                <span class="rounded-full px-4 py-2 text-sm font-black {{ $profile->available_now ? 'bg-gold-400 text-navy-900' : 'bg-white/10 text-slate-200' }}">
                    {{ $profile->available_now ? 'Available now' : 'Currently unavailable' }}
                </span>
            </div>
            <p class="mt-4 text-sm text-slate-400">Distance to you is not shown &mdash; Oncall does not yet collect reliable provider coordinates.</p>
        </div>
    </section>

    <main class="mx-auto grid max-w-4xl gap-6 px-6 py-10 lg:grid-cols-[1fr_20rem]">
        <div class="grid gap-6">
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-black">Verification &amp; track record</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="rounded-full bg-gold-100 px-3 py-1 text-sm font-bold text-navy-900">&#10003; Identity verified</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold">Provider profile approved</span>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-slate-500">Rating</dt>
                        <dd class="text-lg font-bold">{{ $profile->rating_cached ? number_format((float) $profile->rating_cached, 1).' / 5' : 'No ratings yet' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Completed services</dt>
                        <dd class="text-lg font-bold">{{ $profile->completed_jobs_cached }}</dd>
                    </div>
                </dl>
                @if($profile->credentials_metadata)
                    <div class="mt-5">
                        <p class="text-sm text-slate-500">Credentials provided by the provider</p>
                        <ul class="mt-2 grid gap-1 text-sm">
                            @foreach($profile->credentials_metadata as $credential)
                                <li class="rounded-lg bg-slate-50 px-3 py-2">{{ $credential }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-2 text-xs text-slate-500">Credential text is self-declared unless marked verified by Oncall.</p>
                    </div>
                @endif
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xl font-black">Services offered</h2>
                <div class="mt-4 grid gap-3">
                    @foreach($profile->providerServices as $providerService)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-bold">{{ $providerService->service->name }}</p>
                                @if($providerService->rate_from || $providerService->rate_to)
                                    <p class="text-sm text-slate-600">PHP {{ $providerService->rate_from ?? '0' }} &ndash; {{ $providerService->rate_to ?? 'open' }} {{ str($providerService->rate_type ?? '')->replace('_', ' ') }}</p>
                                @endif
                            </div>
                            @if($providerService->experience_text)
                                <p class="mt-1 text-sm text-slate-600">{{ $providerService->experience_text }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            @if($reveal && $profile->bio)
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-xl font-black">About</h2>
                    <p class="mt-3 whitespace-pre-line text-slate-700">{{ $profile->bio }}</p>
                </section>
            @elseif(! $reveal)
                <section class="rounded-2xl bg-white p-6 text-sm text-slate-600 shadow-sm ring-1 ring-slate-200">
                    The provider's written introduction is shown to verified Service Finders only, because free-text fields can carry contact details.
                </section>
            @endif

            <x-safety-notice />
        </div>

        <aside class="self-start rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @if($canRequest)
                <p class="font-bold">Ready to book?</p>
                <p class="mt-1 text-sm text-slate-600">Send a service request. Contact details are shared only after the provider accepts and the booking is confirmed.</p>
                <a class="mt-4 block rounded-lg bg-gold-400 px-4 py-3 text-center font-bold text-navy-900 hover:bg-gold-500" href="{{ route('service-requests.create', $profile) }}">Request service</a>
            @elseif($reveal)
                <p class="font-bold">Requests are on hold</p>
                <p class="mt-1 text-sm text-slate-600">Your account cannot send service requests right now. Complete identity verification or resolve any account restriction first.</p>
                <a class="mt-4 block rounded-lg border border-slate-300 px-4 py-3 text-center font-bold" href="{{ route('verification.index') }}">Go to verification</a>
            @else
                <p class="font-bold">Sign in to request this provider</p>
                <p class="mt-1 text-sm text-slate-600">Guests can browse availability. Provider identity and contact details unlock for verified Service Finders.</p>
                <a class="mt-4 block rounded-lg bg-gold-400 px-4 py-3 text-center font-bold text-navy-900 hover:bg-gold-500" href="{{ route('login') }}">Sign in or register</a>
            @endif
        </aside>
    </main>
</x-layouts.public>
