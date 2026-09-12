@php
    $isProvider = auth()->user()->role === App\Enums\UserRole::ServiceProvider;
@endphp

<x-layouts.app title="Bookings and jobs" description="Confirmed bookings, with contact details, messages, payment, and status history kept on record.">
    @if($jobs->isEmpty())
        <x-empty-state icon="briefcase" title="No confirmed bookings yet" message="Accepted service requests become bookings and show up here.">
            @unless($isProvider)<x-ui.button :href="route('home').'#find-help'" variant="primary">Find help</x-ui.button>@endunless
        </x-empty-state>
    @else
        <div class="card">
            <ul class="stack-list">
                @foreach($jobs as $job)
                    <li>
                        <a class="flex flex-col gap-3 px-5 py-4 hover:bg-surface-muted sm:flex-row sm:items-center sm:justify-between sm:px-6" href="{{ route('jobs.show', $job) }}">
                            <span class="min-w-0">
                                <span class="block text-xs font-semibold text-navy-700">{{ $job->serviceRequest->service->name }}</span>
                                <span class="mt-0.5 block truncate text-base font-semibold text-ink">{{ $job->serviceRequest->title }}</span>
                                <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-ink-muted">
                                    <span>{{ $isProvider ? 'Customer: '.$job->serviceFinder->name : 'Provider: '.$job->provider->name }}</span>
                                    <span class="font-medium text-ink tabular-nums">₱{{ number_format((float) $job->agreed_price, 2) }}</span>
                                    <span>{{ $job->created_at->format('M j, Y') }}</span>
                                </span>
                            </span>
                            <span class="flex items-center gap-3">
                                <x-ui.status-badge :status="$job->status" />
                                <x-ui.icon name="chevron-right" class="size-4 text-ink-muted" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        @if($jobs->hasPages())<div class="mt-6">{{ $jobs->links() }}</div>@endif
    @endif
</x-layouts.app>
