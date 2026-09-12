@php
    $isFinder = auth()->id() === $serviceRequest->service_finder_id;
    $peso = fn ($amount): string => '₱'.number_format((float) $amount, 2);
@endphp

<x-layouts.app :title="$serviceRequest->title" :eyebrow="$serviceRequest->service->name">
    <x-slot:actions><x-ui.status-badge :status="$serviceRequest->status" class="px-3 py-1.5 text-sm" /></x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <div class="grid content-start gap-6">
            <section class="card card-pad">
                <h2 class="h3">Request details</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">Location</dt><dd class="mt-0.5 font-medium text-ink">{{ $serviceRequest->municipality?->name }}, {{ $serviceRequest->province->name }}</dd></div>
                    <div><dt class="text-ink-muted">How soon</dt><dd class="mt-0.5 font-medium text-ink">{{ match ($serviceRequest->urgency) { App\Enums\ServiceUrgency::Immediate => 'As soon as possible', App\Enums\ServiceUrgency::SameDay => 'Today', App\Enums\ServiceUrgency::Scheduled => 'On a specific date' } }}</dd></div>
                    @if($serviceRequest->needed_at)<div><dt class="text-ink-muted">Needed on</dt><dd class="mt-0.5 font-medium text-ink">{{ $serviceRequest->needed_at->format('D, M j, Y · g:i A') }}</dd></div>@endif
                    @if($serviceRequest->budget_min || $serviceRequest->budget_max)<div><dt class="text-ink-muted">Budget</dt><dd class="mt-0.5 font-medium text-ink tabular-nums">{{ $serviceRequest->budget_min ? $peso($serviceRequest->budget_min) : '₱0.00' }} &ndash; {{ $serviceRequest->budget_max ? $peso($serviceRequest->budget_max) : 'Open' }}</dd></div>@endif
                    <div><dt class="text-ink-muted">Sent</dt><dd class="mt-0.5 font-medium text-ink">{{ $serviceRequest->created_at->format('M j, Y · g:i A') }}</dd></div>
                </dl>
                @if($serviceRequest->description)
                    <div class="mt-5 border-t border-line pt-5">
                        <h3 class="text-sm font-semibold text-ink">Description</h3>
                        <p class="mt-1.5 whitespace-pre-line text-ink-secondary">{{ $serviceRequest->description }}</p>
                    </div>
                @endif
            </section>

            @can('respond', $serviceRequest)
                <section class="card card-pad ring-gold-300">
                    <h2 class="h3">Respond to this request</h2>
                    <p class="mt-1 text-sm text-ink-secondary">Accepting confirms the booking, shares contact details with the customer, and records the agreed price.</p>
                    <form class="mt-5 grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end" method="POST" action="{{ route('service-requests.accept', $serviceRequest) }}">
                        @csrf @method('PATCH')
                        <x-form.input name="agreed_price" type="number" label="Agreed price (₱)" min="0.01" step="0.01" inputmode="decimal" :value="$serviceRequest->budget_max" required />
                        <x-ui.button variant="primary" size="lg" data-loading-text="Confirming…">Accept and confirm booking</x-ui.button>
                    </form>
                    <form class="mt-3" method="POST" action="{{ route('service-requests.decline', $serviceRequest) }}">
                        @csrf @method('PATCH')
                        <x-ui.button variant="ghost" size="sm" data-loading-text="Declining…">Decline this request</x-ui.button>
                    </form>
                </section>
            @endcan

            @if($serviceRequest->job)
                <x-ui.alert tone="success" title="This request is now a confirmed booking">
                    Contact details, messages, and status updates live on the booking record.
                    <a class="mt-2 inline-flex items-center gap-1 font-semibold underline" href="{{ route('jobs.show', $serviceRequest->job) }}">Open booking <x-ui.icon name="arrow-right" class="size-4" /></a>
                </x-ui.alert>
            @endif

            <x-safety-notice variant="compact" />
        </div>

        <aside class="grid content-start gap-4">
            <div class="card card-pad">
                <p class="eyebrow text-navy-700">{{ $isFinder ? 'Provider' : 'Requested by' }}</p>
                @php($party = $isFinder ? $serviceRequest->requestedProvider : $serviceRequest->serviceFinder)
                <div class="mt-3 flex items-center gap-3">
                    <x-ui.avatar :name="$party->name" size="md" />
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-ink">{{ $party->name }}</p>
                        <x-ui.rating :value="$party->rating_cached" :count="$party->reviews_count" />
                    </div>
                </div>
                <p class="mt-3 text-xs text-ink-muted">Phone and email are shared only after the booking is confirmed.</p>
            </div>

            @can('cancel', $serviceRequest)
                <form method="POST" action="{{ route('service-requests.cancel', $serviceRequest) }}">
                    @csrf @method('PATCH')
                    <x-ui.button variant="danger" block data-loading-text="Cancelling…">Cancel request</x-ui.button>
                </form>
            @endcan
        </aside>
    </div>
</x-layouts.app>
