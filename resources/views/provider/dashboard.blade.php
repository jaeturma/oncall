<x-layouts.app title="Provider overview" eyebrow="Service provider" description="Manage your availability, respond to requests, and keep every job on record.">
    @if($profile)
        <x-slot:actions>
            <form class="flex items-center gap-2" method="POST" action="{{ route('provider.availability.update', $profile) }}" data-skip-loading>
                @csrf @method('PATCH')
                <label class="sr-only" for="availability_status">Availability status</label>
                <select id="availability_status" name="availability_status" class="select" data-autosubmit>
                    @foreach(App\Enums\AvailabilityStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($profile->availability_status === $status)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-secondary btn-sm">Update</button></noscript>
            </form>
        </x-slot:actions>
    @endif

    <div class="grid gap-6">
        <x-ui.onboarding-checklist :items="$onboarding" />

        @unless($profile)
            <x-empty-state icon="briefcase" title="Set up your provider profile" message="Tell customers what you do and where you work. Your profile appears in searches once it is approved and your identity is verified.">
                <x-ui.button :href="route('provider.profiles.create')" variant="primary" size="lg">Create provider profile</x-ui.button>
            </x-empty-state>
        @else
            @if($identityVerified && $profile->verification_status !== App\Enums\VerificationStatus::Verified)
                <x-ui.alert tone="warning" title="Profile under review">Your identity is verified. Your provider profile is awaiting Oncall approval before it appears in search results.</x-ui.alert>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card label="New requests" :value="$metrics['new_requests']" icon="inbox" tone="warning" :href="route('service-requests.index')" />
                <x-ui.stat-card label="Active services" :value="$metrics['active_jobs']" icon="briefcase" tone="brand" :href="route('jobs.index')" />
                <x-ui.stat-card label="Completed services" :value="$metrics['completed_jobs']" icon="check-circle" tone="success" :href="route('jobs.index')" />
                <x-ui.stat-card label="Earnings available" :value="'₱'.number_format((float) $metrics['earnings_available'], 2)" icon="wallet" tone="accent" :href="route('wallet.index')" />
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
                <section class="card">
                    <div class="card-header">
                        <h2 class="h3">Requests waiting for you</h2>
                        <a class="text-sm font-semibold text-navy-800 hover:underline" href="{{ route('service-requests.index') }}">View all</a>
                    </div>
                    @if($incomingRequests->isEmpty())
                        <div class="px-5 py-8 text-center sm:px-6"><p class="font-medium text-ink">No new requests</p><p class="mt-1 text-sm text-ink-secondary">Requests from customers will appear here. Being available now puts you first in results.</p></div>
                    @else
                        <ul class="stack-list">
                            @foreach($incomingRequests as $serviceRequest)
                                <li>
                                    <a class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-surface-muted sm:px-6" href="{{ route('service-requests.show', $serviceRequest) }}">
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold text-ink">{{ $serviceRequest->title }}</span>
                                            <span class="block truncate text-xs text-ink-muted">{{ $serviceRequest->service->name }} &middot; {{ $serviceRequest->municipality?->name ?? $serviceRequest->province->name }} &middot; {{ str($serviceRequest->urgency->value)->replace('_', ' ')->lower()->ucfirst() }}</span>
                                        </span>
                                        <x-ui.icon name="chevron-right" class="size-4 text-ink-muted" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="card card-pad">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="h3">Your public profile</h2>
                            <p class="mt-1 text-sm text-ink-secondary">{{ $profile->municipality->name }}, {{ $profile->province->name }}@if($profile->service_radius_km) &middot; {{ $profile->service_radius_km }} km radius @endif</p>
                        </div>
                        <x-ui.status-badge :status="$profile->verification_status" />
                    </div>
                    <div class="mt-3"><x-ui.availability-badge :status="$profile->availability_status" /></div>
                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm">
                        <x-ui.rating :value="$profile->rating_cached" />
                        <span class="text-ink-secondary"><span class="font-semibold text-ink tabular-nums">{{ $profile->completed_jobs_cached }}</span> completed</span>
                    </div>
                    <div class="mt-4">
                        <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Services offered</p>
                        <ul class="mt-2 flex flex-wrap gap-1.5">
                            @foreach($profile->providerServices as $providerService)<li class="badge badge-brand">{{ $providerService->service->name }}</li>@endforeach
                        </ul>
                    </div>
                    @if($profile->bio)
                        <div class="mt-4"><p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">About</p><p class="mt-1 line-clamp-3 text-sm text-ink-secondary">{{ $profile->bio }}</p></div>
                    @endif
                    @if($profile->credentials_metadata)
                        <div class="mt-4"><p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Declared credentials</p><ul class="mt-2 flex flex-wrap gap-1.5">@foreach($profile->credentials_metadata as $credential)<li class="badge badge-outline">{{ $credential }}</li>@endforeach</ul></div>
                    @endif
                    <div class="mt-5 flex flex-wrap gap-2 border-t border-line pt-5">
                        <x-ui.button :href="route('provider.profiles.edit', $profile)" variant="secondary" size="sm" icon="cog">Edit profile</x-ui.button>
                        @if($identityVerified && $profile->verification_status === App\Enums\VerificationStatus::Verified)
                            <x-ui.button :href="route('providers.show', $profile)" variant="ghost" size="sm" icon="user">Preview as customer</x-ui.button>
                        @endif
                    </div>
                </section>
            </div>
        @endunless

        <x-safety-notice variant="compact" />
    </div>
</x-layouts.app>
