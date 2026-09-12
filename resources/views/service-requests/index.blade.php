@php
    $isProvider = auth()->user()->role === App\Enums\UserRole::ServiceProvider;
@endphp

<x-layouts.app :title="$isProvider ? 'Incoming requests' : 'My requests'" :description="$isProvider ? 'Requests from customers who chose you. Accept to confirm a booking and unlock contact details.' : 'Requests you sent to providers. Accepted requests become bookings.'">
    @unless($isProvider)
        <x-slot:actions><x-ui.button :href="route('home').'#find-help'" variant="primary" icon="search">New request</x-ui.button></x-slot:actions>
    @endunless

    @if($requests->isEmpty())
        <x-empty-state icon="inbox" title="No requests yet" :message="$isProvider ? 'When a customer requests your services, it will appear here. Staying available puts you first in search results.' : 'When you request a service, it will appear here.'">
            @unless($isProvider)<x-ui.button :href="route('home').'#find-help'" variant="primary">Find help</x-ui.button>@endunless
        </x-empty-state>
    @else
        <div class="card">
            <ul class="stack-list">
                @foreach($requests as $serviceRequest)
                    <li>
                        <a class="flex flex-col gap-3 px-5 py-4 hover:bg-surface-muted sm:flex-row sm:items-center sm:justify-between sm:px-6" href="{{ route('service-requests.show', $serviceRequest) }}">
                            <span class="min-w-0">
                                <span class="block text-xs font-semibold text-navy-700">{{ $serviceRequest->service->name }}</span>
                                <span class="mt-0.5 block truncate text-base font-semibold text-ink">{{ $serviceRequest->title }}</span>
                                <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-ink-muted">
                                    <span class="inline-flex items-center gap-1"><x-ui.icon name="map-pin" class="size-3.5" />{{ $serviceRequest->municipality?->name ?? $serviceRequest->province->name }}</span>
                                    <span class="inline-flex items-center gap-1"><x-ui.icon name="clock" class="size-3.5" />{{ str($serviceRequest->urgency->value)->replace('_', ' ')->lower()->ucfirst() }}</span>
                                    <span>{{ $isProvider ? 'From '.$serviceRequest->serviceFinder->name : 'To '.$serviceRequest->requestedProvider->name }}</span>
                                    <span>{{ $serviceRequest->created_at->diffForHumans() }}</span>
                                </span>
                            </span>
                            <span class="flex items-center gap-3">
                                <x-ui.status-badge :status="$serviceRequest->status" />
                                <x-ui.icon name="chevron-right" class="size-4 text-ink-muted" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        @if($requests->hasPages())<div class="mt-6">{{ $requests->links() }}</div>@endif
    @endif
</x-layouts.app>
