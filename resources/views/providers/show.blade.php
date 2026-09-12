@php
    $services = $profile->providerServices->map(fn ($providerService) => $providerService->service)->filter();
    $primaryService = $services->first()?->name ?? 'Service Provider';
    $displayName = $reveal ? $profile->user->name : 'Verified '.$primaryService.' #'.str_pad((string) $profile->id, 4, '0', STR_PAD_LEFT);
    $verifiedTypes = collect($profile->verified_document_types ?? []);
    $badges = collect([
        $verifiedTypes->isNotEmpty() ? 'identity' : null,
        $verifiedTypes->contains('DRIVERS_LICENSE') ? 'drivers-license' : null,
        $verifiedTypes->contains('PROFESSIONAL_CREDENTIAL') ? 'professional-license' : null,
        ($profile->mobile_verified ?? false) ? 'mobile' : null,
        ($profile->email_verified ?? false) ? 'email' : null,
        'provider',
    ])->filter();
    $peso = fn ($amount): string => '₱'.number_format((float) $amount, 2);
    $backUrl = url()->previous() !== url()->current() && str(url()->previous())->contains('/find-help') ? url()->previous() : route('home').'#find-help';
@endphp

<x-layouts.public :title="$displayName">
    {{-- Identity header --}}
    <section class="border-b border-line bg-surface">
        <div class="container-x py-6 sm:py-8">
            <a class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-navy-900" href="{{ $backUrl }}"><x-ui.icon name="arrow-left" class="size-4" />Back to results</a>
            <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-start">
                <x-ui.avatar :name="$reveal ? $profile->user->name : null" :anonymous="! $reveal" size="xl" />
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.availability-badge :status="$profile->availability_status" size="md" />
                    </div>
                    <h1 class="h1 mt-2">{{ $displayName }}</h1>
                    <p class="mt-1 text-base font-medium text-ink-secondary">{{ $primaryService }}</p>
                    <p class="mt-2 flex items-start gap-1.5 text-sm text-ink-muted">
                        <x-ui.icon name="map-pin" class="mt-0.5 size-4" />
                        <span>Serving {{ $profile->municipality->name }}, {{ $profile->province->name }}@if($profile->service_radius_km) &middot; up to {{ $profile->service_radius_km }} km @endif</span>
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1.5">
                        <x-ui.rating :value="$profile->rating_cached" :count="$reviewsCount" size="md" />
                        <span class="text-sm text-ink-secondary"><span class="font-semibold text-ink tabular-nums">{{ $profile->completed_jobs_cached }}</span> completed {{ str('service')->plural($profile->completed_jobs_cached) }}</span>
                    </div>
                    <ul class="mt-4 flex flex-wrap gap-2" aria-label="Verifications">
                        @foreach($badges as $badge)<li><x-ui.verification-badge :type="$badge" size="md" /></li>@endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <div class="container-x grid gap-8 py-8 pb-28 lg:grid-cols-[minmax(0,1fr)_21rem] lg:py-10 lg:pb-16">
        <div class="grid content-start gap-6">
            @if($reveal && $profile->bio)
                <section class="card card-pad">
                    <h2 class="h3">About</h2>
                    <p class="mt-3 whitespace-pre-line text-ink-secondary">{{ $profile->bio }}</p>
                </section>
            @elseif(! $reveal)
                <x-ui.alert tone="info" icon="eye-slash">The provider's written introduction is shown to verified customers only, because free-text fields can contain contact details.</x-ui.alert>
            @endif

            <section class="card">
                <div class="card-header"><h2 class="h3">Services offered</h2><span class="badge badge-neutral">{{ $services->count() }} {{ str('service')->plural($services->count()) }}</span></div>
                <ul class="stack-list">
                    @foreach($profile->providerServices as $providerService)
                        @continue(! $providerService->service)
                        <li class="flex flex-wrap items-start justify-between gap-3 px-5 py-4 sm:px-6">
                            <div class="min-w-0">
                                <p class="font-semibold text-ink">{{ $providerService->service->name }}</p>
                                @if($providerService->service->category)<p class="text-xs text-ink-muted">{{ $providerService->service->category->name }}</p>@endif
                                @if($providerService->experience_text)<p class="mt-1 text-sm text-ink-secondary">{{ $providerService->experience_text }}</p>@endif
                            </div>
                            @if($providerService->rate_from || $providerService->rate_to)
                                <p class="text-sm font-semibold text-ink tabular-nums">
                                    {{ $providerService->rate_from ? $peso($providerService->rate_from) : 'From ₱0.00' }}@if($providerService->rate_to) &ndash; {{ $peso($providerService->rate_to) }}@endif
                                    @if($providerService->rate_type)<span class="font-normal text-ink-muted"> {{ str($providerService->rate_type)->replace('_', ' ') }}</span>@endif
                                </p>
                            @else
                                <p class="text-sm text-ink-muted">Rate on request</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="card card-pad">
                <h2 class="h3">Verification &amp; credentials</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    <li class="flex items-start gap-3 rounded-xl bg-success-50 p-3.5 text-sm text-success-800">
                        <x-ui.icon name="shield-check" class="mt-0.5 size-5 text-success-600" />
                        <span><span class="font-semibold">Identity verified.</span> A government ID was reviewed and approved by Oncall staff.</span>
                    </li>
                    <li class="flex items-start gap-3 rounded-xl bg-success-50 p-3.5 text-sm text-success-800">
                        <x-ui.icon name="check-badge" class="mt-0.5 size-5 text-success-600" />
                        <span><span class="font-semibold">Approved provider.</span> This profile passed Oncall's provider review.</span>
                    </li>
                    @if($verifiedTypes->contains('DRIVERS_LICENSE'))
                        <li class="flex items-start gap-3 rounded-xl bg-success-50 p-3.5 text-sm text-success-800"><x-ui.icon name="identification" class="mt-0.5 size-5 text-success-600" /><span><span class="font-semibold">Driver's license verified.</span> Reviewed and approved by Oncall staff.</span></li>
                    @endif
                    @if($verifiedTypes->contains('PROFESSIONAL_CREDENTIAL'))
                        <li class="flex items-start gap-3 rounded-xl bg-success-50 p-3.5 text-sm text-success-800"><x-ui.icon name="academic-cap" class="mt-0.5 size-5 text-success-600" /><span><span class="font-semibold">Professional license verified.</span> Reviewed and approved by Oncall staff.</span></li>
                    @endif
                </ul>
                @if($profile->credentials_metadata)
                    <div class="mt-5">
                        <p class="text-sm font-semibold text-ink">Credentials declared by the provider</p>
                        <ul class="mt-2 flex flex-wrap gap-2">
                            @foreach($profile->credentials_metadata as $credential)<li class="badge badge-outline py-1.5 text-[13px] font-medium">{{ $credential }}</li>@endforeach
                        </ul>
                        <p class="mt-2 text-xs text-ink-muted">Declared credentials are self-reported unless they appear as a verified badge above.</p>
                    </div>
                @endif
            </section>

            <section class="card card-pad">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="h3">Reviews</h2>
                    <x-ui.rating :value="$profile->rating_cached" :count="$reviewsCount" />
                </div>
                @if($reviews->isNotEmpty())
                    <ul class="mt-4 grid gap-4">
                        @foreach($reviews as $review)
                            <li class="rounded-xl border border-line p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <x-ui.avatar :name="$review->reviewer->name" size="xs" />
                                        <p class="text-sm font-semibold text-ink">{{ str($review->reviewer->name)->before(' ') }}</p>
                                    </div>
                                    <p class="flex items-center gap-2 text-xs text-ink-muted">
                                        <span class="text-gold-500" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                                        {{ $review->created_at->format('M Y') }}
                                    </p>
                                </div>
                                <p class="mt-2 whitespace-pre-line text-sm text-ink-secondary">{{ $review->comment }}</p>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-xs text-ink-muted">Reviews can only be written by customers who completed a booking with this provider on Oncall.</p>
                @else
                    <p class="mt-3 text-sm text-ink-secondary">No written reviews yet. Reviews come only from completed bookings on Oncall.</p>
                @endif
            </section>

            <x-safety-notice />
        </div>

        {{-- Request sidebar --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="card card-pad">
                @if($canRequest)
                    <p class="eyebrow text-navy-700">Ready to book?</p>
                    <p class="mt-2 text-base font-semibold text-ink">Request a service from {{ str($displayName)->before(' ') }}</p>
                    <p class="mt-1 text-sm text-ink-secondary">Tell them what you need, when, and your budget. Contact details are shared only after the provider accepts.</p>
                    <x-ui.button :href="route('service-requests.create', $profile)" variant="primary" size="lg" block class="mt-4">Request service</x-ui.button>
                @elseif($reveal)
                    <p class="text-base font-semibold text-ink">Requests are on hold</p>
                    <p class="mt-1 text-sm text-ink-secondary">Your account cannot send service requests right now. Complete identity verification or resolve any account restriction first.</p>
                    <x-ui.button :href="route('verification.index')" variant="secondary" block class="mt-4">Go to verification</x-ui.button>
                @else
                    <p class="text-base font-semibold text-ink">Sign in to request this provider</p>
                    <p class="mt-1 text-sm text-ink-secondary">Guests can browse availability. The provider's name and contact details unlock for verified customers.</p>
                    <x-ui.button :href="route('login')" variant="primary" size="lg" block class="mt-4">Sign in or register</x-ui.button>
                @endif

                <dl class="mt-5 grid gap-3 border-t border-line pt-5 text-sm">
                    <div class="flex items-center justify-between gap-3"><dt class="text-ink-muted">Availability</dt><dd class="font-medium text-ink">{{ $profile->availability_status->label() }}</dd></div>
                    <div class="flex items-center justify-between gap-3"><dt class="text-ink-muted">Services</dt><dd class="font-medium text-ink">{{ $services->count() }}</dd></div>
                    <div class="flex items-center justify-between gap-3"><dt class="text-ink-muted">Completed</dt><dd class="font-medium text-ink tabular-nums">{{ $profile->completed_jobs_cached }}</dd></div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-ink-muted">Distance</dt>
                        <dd class="text-right font-medium text-ink">
                            @if($distanceKm !== null)~{{ number_format($distanceKm, $distanceKm < 10 ? 1 : 0) }} km from your search location<span class="block text-xs font-normal text-ink-muted">approximate, not live GPS</span>@else<span class="font-normal text-ink-muted">Not available for this location</span>@endif
                        </dd>
                    </div>
                </dl>
            </div>
            <x-safety-notice variant="compact" class="mt-4 hidden lg:flex" />
        </aside>
    </div>

    {{-- Mobile action bar --}}
    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-surface/95 p-3 backdrop-blur lg:hidden" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
        <div class="mx-auto flex w-full max-w-7xl items-center gap-3 px-1 sm:px-3">
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-ink">{{ $displayName }}</p>
                <p class="truncate text-xs text-ink-muted">{{ $primaryService }} &middot; {{ $profile->municipality->name }}</p>
            </div>
            @if($canRequest)
                <x-ui.button :href="route('service-requests.create', $profile)" variant="primary">Request service</x-ui.button>
            @elseif($reveal)
                <x-ui.button :href="route('verification.index')" variant="secondary">Verify to request</x-ui.button>
            @else
                <x-ui.button :href="route('login')" variant="primary">Sign in to request</x-ui.button>
            @endif
        </div>
    </div>
</x-layouts.public>
