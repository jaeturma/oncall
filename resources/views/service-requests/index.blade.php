<x-layouts.app title="Service requests">
    <div class="grid gap-4">
        @forelse($requests as $serviceRequest)
            <a class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:ring-gold-400" href="{{ route('service-requests.show', $serviceRequest) }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><p class="text-sm font-bold text-navy-800">{{ $serviceRequest->service->name }}</p><h2 class="mt-1 text-xl font-black">{{ $serviceRequest->title }}</h2></div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ str($serviceRequest->status->value)->replace('_', ' ')->title() }}</span>
                </div>
                <p class="mt-3 text-slate-600">{{ $serviceRequest->municipality?->name ?? $serviceRequest->province->name }} · {{ str($serviceRequest->urgency->value)->replace('_', ' ')->title() }}</p>
            </a>
        @empty
            <div class="rounded-2xl bg-white p-8 text-center text-slate-600 shadow-sm ring-1 ring-slate-200">No service requests yet.</div>
        @endforelse
        {{ $requests->links() }}
    </div>
</x-layouts.app>
