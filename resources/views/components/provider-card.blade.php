@props(['profile', 'reveal' => false, 'position' => null])

@php
    /**
     * Provider result card. Guest-safe by design: when $reveal is false the
     * controller never loads the user relation, so the name cannot leak here.
     */
    $services = $profile->providerServices->map(fn ($providerService) => $providerService->service?->name)->filter()->values();
    $primaryService = $services->first() ?? 'Service Provider';
    $otherServices = $services->slice(1);
    $displayName = $reveal ? $profile->user->name : 'Verified '.$primaryService.' #'.str_pad((string) $profile->id, 4, '0', STR_PAD_LEFT);
    $verifiedTypes = collect($profile->verified_document_types ?? []);
    $badges = collect([
        $verifiedTypes->isNotEmpty() ? 'identity' : null,
        $verifiedTypes->contains('DRIVERS_LICENSE') ? 'drivers-license' : null,
        $verifiedTypes->contains('PROFESSIONAL_CREDENTIAL') ? 'professional-license' : null,
    ])->filter();
    $profileUrl = route('providers.show', $profile).'?'.http_build_query(array_filter(['from_province_id' => request('province_id'), 'from_municipality_id' => request('municipality_id')]));
@endphp

<article {{ $attributes->class(['card card-interactive flex flex-col overflow-hidden']) }} aria-label="{{ $displayName }}">
    <div class="flex flex-1 gap-4 p-5">
        <x-ui.avatar :name="$reveal ? $profile->user->name : null" :anonymous="! $reveal" size="lg" class="mt-0.5" />
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.availability-badge :status="$profile->availability_status" />
            </div>
            <h3 class="mt-2 truncate text-lg font-semibold text-ink">
                <a class="hover:text-navy-700 hover:underline" href="{{ $profileUrl }}">{{ $displayName }}</a>
            </h3>
            <p class="text-sm font-medium text-ink-secondary">{{ $primaryService }}</p>
            <p class="mt-1.5 flex items-start gap-1.5 text-sm text-ink-muted">
                <x-ui.icon name="map-pin" class="mt-0.5 size-4" />
                <span>{{ $profile->municipality->name }}, {{ $profile->province->name }}@if($profile->distance_km !== null)<span class="text-ink-muted"> &middot; ~{{ number_format($profile->distance_km, $profile->distance_km < 10 ? 1 : 0) }} km away <span class="text-xs">(approximate)</span></span>@endif</span>
            </p>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                <x-ui.rating :value="$profile->rating_cached" />
                <span class="text-ink-secondary"><span class="font-semibold text-ink tabular-nums">{{ $profile->completed_jobs_cached }}</span> completed {{ str('service')->plural($profile->completed_jobs_cached) }}</span>
            </div>

            @if($badges->isNotEmpty())
                <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="Verifications">
                    @foreach($badges as $badge)<li><x-ui.verification-badge :type="$badge" /></li>@endforeach
                </ul>
            @endif

            @if($otherServices->isNotEmpty())
                <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="Also offers">
                    @foreach($otherServices->take(3) as $service)<li class="badge badge-outline">{{ $service }}</li>@endforeach
                    @if($otherServices->count() > 3)<li class="badge badge-outline">+{{ $otherServices->count() - 3 }} more</li>@endif
                </ul>
            @endif
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 border-t border-line bg-surface-muted px-5 py-3">
        <x-ui.button :href="$profileUrl" variant="secondary" size="sm">View profile</x-ui.button>
        @if($reveal)
            <x-ui.button :href="route('service-requests.create', $profile)" variant="primary" size="sm">Request service</x-ui.button>
        @else
            <p class="flex items-center gap-1.5 text-xs text-ink-muted"><x-ui.icon name="lock-closed" class="size-3.5" />Name and contact details are hidden. Sign in to request this provider.</p>
        @endif
    </div>
</article>
