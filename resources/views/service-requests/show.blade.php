<x-layouts.app title="Service request">
    @if(session('status'))<p class="mb-5 rounded-lg bg-gold-50 p-4 text-navy-900">{{ session('status') }}</p>@endif
    <article class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div><p class="text-sm font-bold uppercase tracking-widest text-navy-800">{{ $serviceRequest->service->name }}</p><h2 class="mt-2 text-2xl font-black">{{ $serviceRequest->title }}</h2></div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ str($serviceRequest->status->value)->replace('_', ' ')->title() }}</span>
        </div>
        <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="font-bold">Location</dt><dd class="text-slate-600">{{ $serviceRequest->municipality?->name }}, {{ $serviceRequest->province->name }}</dd></div>
            <div><dt class="font-bold">Urgency</dt><dd class="text-slate-600">{{ str($serviceRequest->urgency->value)->replace('_', ' ')->title() }}</dd></div>
            @if($serviceRequest->needed_at)<div><dt class="font-bold">Needed at</dt><dd class="text-slate-600">{{ $serviceRequest->needed_at->format('M j, Y g:i A') }}</dd></div>@endif
            @if($serviceRequest->budget_min || $serviceRequest->budget_max)<div><dt class="font-bold">Budget</dt><dd class="text-slate-600">PHP {{ $serviceRequest->budget_min ?? '0.00' }} – {{ $serviceRequest->budget_max ?? 'Open' }}</dd></div>@endif
        </dl>
        @if($serviceRequest->description)<div class="mt-6"><h3 class="font-bold">Description</h3><p class="mt-2 whitespace-pre-line text-slate-700">{{ $serviceRequest->description }}</p></div>@endif
        <div class="mt-8 flex flex-wrap gap-3">
            @can('respond', $serviceRequest)
                <form class="flex flex-wrap items-end gap-3" method="POST" action="{{ route('service-requests.accept', $serviceRequest) }}">
                    @csrf @method('PATCH')
                    <label class="grid gap-1 font-semibold">Agreed price
                        <input class="rounded-lg border-slate-300" type="number" name="agreed_price" min="0.01" step="0.01" value="{{ old('agreed_price', $serviceRequest->budget_max) }}" required>
                        @error('agreed_price')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
                    </label>
                    <button class="rounded-lg bg-navy-900 px-5 py-3 font-bold text-white" type="submit">Accept and confirm booking</button>
                </form>
                <form method="POST" action="{{ route('service-requests.decline', $serviceRequest) }}">@csrf @method('PATCH')<button class="rounded-lg border border-slate-300 px-5 py-3 font-bold" type="submit">Decline</button></form>
            @endcan
            @if($serviceRequest->job)
                <a class="rounded-lg bg-navy-900 px-5 py-3 font-bold text-white" href="{{ route('jobs.show', $serviceRequest->job) }}">View confirmed booking</a>
            @endif
            @can('cancel', $serviceRequest)
                <form method="POST" action="{{ route('service-requests.cancel', $serviceRequest) }}">@csrf @method('PATCH')<button class="rounded-lg border border-red-300 px-5 py-3 font-bold text-red-700" type="submit">Cancel request</button></form>
            @endcan
        </div>
    </article>
</x-layouts.app>
