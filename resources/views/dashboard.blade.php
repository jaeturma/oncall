<x-layouts.app title="Welcome back, {{ str(auth()->user()->name)->before(' ') }}" eyebrow="Your overview" description="Track your requests and bookings, and keep everything on record.">
    <x-slot:actions>
        <x-ui.button :href="route('home').'#find-help'" variant="primary" icon="search">Find help</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-6">
        <x-ui.onboarding-checklist :items="$onboarding" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-card label="Pending requests" :value="$metrics['pending_requests']" icon="inbox" tone="warning" :href="route('service-requests.index')" />
            <x-ui.stat-card label="Active bookings" :value="$metrics['active_jobs']" icon="briefcase" tone="brand" :href="route('jobs.index')" />
            <x-ui.stat-card label="Completed services" :value="$metrics['completed_jobs']" icon="check-circle" tone="success" :href="route('jobs.index')" />
            <x-ui.stat-card label="Unread notifications" :value="$metrics['unread_notifications']" icon="bell" tone="accent" :href="route('notifications.index')" />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="card">
                <div class="card-header">
                    <h2 class="h3">Recent requests</h2>
                    <a class="text-sm font-semibold text-navy-800 hover:underline" href="{{ route('service-requests.index') }}">View all</a>
                </div>
                @if($recentRequests->isEmpty())
                    <div class="px-5 py-8 text-center sm:px-6">
                        <p class="font-medium text-ink">No requests yet</p>
                        <p class="mt-1 text-sm text-ink-secondary">When you request a service, it will appear here.</p>
                    </div>
                @else
                    <ul class="stack-list">
                        @foreach($recentRequests as $serviceRequest)
                            <li>
                                <a class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-surface-muted sm:px-6" href="{{ route('service-requests.show', $serviceRequest) }}">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-ink">{{ $serviceRequest->title }}</span>
                                        <span class="block truncate text-xs text-ink-muted">{{ $serviceRequest->service->name }} &middot; {{ $serviceRequest->municipality?->name ?? $serviceRequest->province->name }} &middot; {{ $serviceRequest->created_at->diffForHumans() }}</span>
                                    </span>
                                    <x-ui.status-badge :status="$serviceRequest->status" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card">
                <div class="card-header">
                    <h2 class="h3">Recent bookings</h2>
                    <a class="text-sm font-semibold text-navy-800 hover:underline" href="{{ route('jobs.index') }}">View all</a>
                </div>
                @if($recentJobs->isEmpty())
                    <div class="px-5 py-8 text-center sm:px-6">
                        <p class="font-medium text-ink">No bookings yet</p>
                        <p class="mt-1 text-sm text-ink-secondary">Accepted requests become bookings and show up here.</p>
                    </div>
                @else
                    <ul class="stack-list">
                        @foreach($recentJobs as $job)
                            <li>
                                <a class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-surface-muted sm:px-6" href="{{ route('jobs.show', $job) }}">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-ink">{{ $job->serviceRequest->title }}</span>
                                        <span class="block truncate text-xs text-ink-muted">{{ $job->serviceRequest->service->name }} &middot; ₱{{ number_format((float) $job->agreed_price, 2) }}</span>
                                    </span>
                                    <x-ui.status-badge :status="$job->status" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        @if($sponsor)
            <section class="card card-pad">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="eyebrow text-navy-700">Sponsorship</p>
                        <h2 class="h3 mt-1">People you sponsored</h2>
                    </div>
                    <a class="text-sm font-semibold text-navy-800 hover:underline" href="{{ route('sponsor.referrals') }}">View details</a>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div><dt class="text-sm text-ink-muted">Sponsored users</dt><dd class="mt-1 text-xl font-bold tabular-nums">{{ $sponsor['count'] }}</dd></div>
                    <div><dt class="text-sm text-ink-muted">Verified</dt><dd class="mt-1 text-xl font-bold tabular-nums">{{ $sponsor['verified'] }}</dd></div>
                    <div><dt class="text-sm text-ink-muted">Pending commission</dt><dd class="mt-1 text-xl font-bold tabular-nums">₱{{ number_format((float) $sponsor['pending'], 2) }}</dd></div>
                    <div><dt class="text-sm text-ink-muted">Earned commission</dt><dd class="mt-1 text-xl font-bold tabular-nums">₱{{ number_format((float) $sponsor['earned'], 2) }}</dd></div>
                    <div><dt class="text-sm text-ink-muted">Wallet balance</dt><dd class="mt-1 text-xl font-bold tabular-nums">₱{{ number_format((float) $walletAvailable, 2) }}</dd></div>
                </dl>
            </section>
        @endif

        <x-safety-notice variant="compact" />
    </div>
</x-layouts.app>
