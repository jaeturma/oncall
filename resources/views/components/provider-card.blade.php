@props(['profile', 'reveal' => false, 'position' => 1])

<article {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200']) }}>
    <div class="h-2 {{ $profile->available_now ? 'bg-gold-400' : 'bg-slate-300' }}"></div>
    <div class="grid gap-5 p-6 sm:grid-cols-[5rem_1fr]">
        <div class="flex size-20 items-center justify-center rounded-2xl bg-slate-100 text-3xl font-black text-navy-800" aria-hidden="true">{{ $position }}</div>
        <div class="min-w-0">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><p class="text-sm font-bold uppercase tracking-widest text-navy-800">{{ $profile->available_now ? 'Available now' : 'Currently unavailable' }}</p><h2 class="mt-1 text-xl font-black">{{ $reveal ? $profile->user->name : 'Local Service Provider' }}</h2></div>
                <span class="rounded-full bg-gold-100 px-3 py-1 text-xs font-bold text-navy-900">Identity verified</span>
            </div>
            <p class="mt-3 text-slate-600">Serving {{ $profile->municipality->name }}, {{ $profile->province->name }}</p>
            <div class="mt-4 flex flex-wrap gap-2">@foreach($profile->providerServices as $providerService)<span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold">{{ $providerService->service->name }}</span>@endforeach</div>
            <div class="mt-5 flex flex-wrap gap-4 text-sm text-slate-600"><span>Rating: {{ $profile->rating_cached ?? 'New' }}</span><span>{{ $profile->completed_jobs_cached }} completed services</span></div>
            @if($reveal)
                <a class="mt-5 inline-flex rounded-lg bg-gold-400 px-4 py-2 font-bold text-navy-900 hover:bg-gold-500" href="{{ route('service-requests.create', $profile) }}">Request service</a>
            @endif
            @unless($reveal)<p class="mt-5 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Provider identity and all direct contact details remain hidden until Oncall permissions allow access.</p>@endunless
        </div>
    </div>
</article>
