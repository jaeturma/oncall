<x-layouts.app title="Booking and job">
    @if(session('status'))<p class="mb-5 rounded-lg bg-gold-50 p-4 text-navy-900">{{ session('status') }}</p>@endif
    <div class="grid gap-6">
        <article class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><p class="text-sm font-bold uppercase tracking-widest text-navy-800">{{ $job->serviceRequest->service->name }}</p><h2 class="mt-2 text-2xl font-black">{{ $job->serviceRequest->title }}</h2></div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ str($job->status->value)->replace('_', ' ')->title() }}</span>
            </div>
            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div><dt class="font-bold">Agreed price</dt><dd class="text-slate-600">PHP {{ $job->agreed_price }}</dd></div>
                <div><dt class="font-bold">Location</dt><dd class="text-slate-600">{{ $job->serviceRequest->municipality?->name }}, {{ $job->serviceRequest->province->name }}</dd></div>
            </dl>

            @can('revealContact', $job)
            <section class="mt-6 rounded-xl bg-gold-50 p-5 text-navy-900">
                <h3 class="font-black">Confirmed booking contacts</h3>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div><p class="font-bold">Service Finder: {{ $job->serviceFinder->name }}</p><p>{{ $job->serviceFinder->email }}</p>@if($job->serviceFinder->phone)<p>{{ $job->serviceFinder->phone }}</p>@endif</div>
                    <div><p class="font-bold">Provider: {{ $job->provider->name }}</p><p>{{ $job->provider->email }}</p>@if($job->provider->phone)<p>{{ $job->provider->phone }}</p>@endif</div>
                </div>
                <p class="mt-4 text-sm">Contact is shown only to the assigned participants after booking confirmation. Keep agreements and job evidence recorded on Oncall.</p>
            </section>
            @else
                <p class="mt-6 rounded-xl bg-amber-50 p-5 text-amber-950">Contact reveal is temporarily restricted for this account. Keep all booking communication on Oncall.</p>
            @endcan

            @if($job->allowedTransitionsFor(auth()->user()) !== [])
                <form class="mt-8 grid gap-4 rounded-xl border border-slate-200 p-5" method="POST" action="{{ route('jobs.status.update', $job) }}">
                    @csrf @method('PATCH')
                    <label class="grid gap-2 font-semibold">Update status
                        <select class="rounded-lg border-slate-300" name="status" required>
                            @foreach($job->allowedTransitionsFor(auth()->user()) as $status)<option value="{{ $status->value }}">{{ str($status->value)->replace('_', ' ')->title() }}</option>@endforeach
                        </select>
                        @error('status')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-2 font-semibold">Notes <span class="text-sm font-normal text-slate-500">Optional, retained in the audit log</span>
                        <textarea class="rounded-lg border-slate-300" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea>
                        @error('notes')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
                    </label>
                    <button class="justify-self-start rounded-lg bg-navy-900 px-5 py-3 font-bold text-white" type="submit">Record status change</button>
                </form>
            @endif
        </article>

        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-xl font-black">Booking record</h2><p class="mt-1 text-sm text-slate-600">Messages, agreements, evidence, and confirmations are retained with this job.</p></div><span class="rounded-full bg-gold-100 px-3 py-1 text-xs font-bold text-navy-900">Stay on Oncall. Stay protected.</span></div>
            <div class="mt-5 grid gap-4">
                @forelse($job->messages as $message)
                    <article class="rounded-xl border border-slate-200 p-4"><div class="flex flex-wrap justify-between gap-2"><p class="font-bold">{{ $message->sender->name }}</p><span class="text-xs font-bold text-navy-800">{{ str($message->type->value)->title() }} · {{ $message->created_at->format('M j, Y g:i A') }}</span></div><p class="mt-2 whitespace-pre-line text-slate-700">{{ $message->body }}</p></article>
                @empty
                    <p class="rounded-xl bg-slate-50 p-4 text-slate-600">No booking messages have been recorded.</p>
                @endforelse
            </div>
            @can('create', [App\Models\JobMessage::class, $job])
            <form class="mt-6 grid gap-4 border-t border-slate-200 pt-6" method="POST" action="{{ route('jobs.messages.store', $job) }}">
                @csrf
                <label class="grid gap-2 font-semibold">Record type
                    <select class="rounded-lg border-slate-300" name="type" required>
                        @foreach(App\Enums\JobMessageType::cases() as $type)<option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ str($type->value)->title() }}</option>@endforeach
                    </select>
                    @error('type')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="grid gap-2 font-semibold">Message or record
                    <textarea class="rounded-lg border-slate-300" name="body" rows="4" maxlength="5000" required>{{ old('body') }}</textarea>
                    @error('body')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <button class="justify-self-start rounded-lg bg-navy-900 px-5 py-3 font-bold text-white" type="submit">Add to booking record</button>
            </form>
            @else
                <p class="mt-6 rounded-xl bg-amber-50 p-4 text-amber-950">Messaging is temporarily restricted for this account.</p>
            @endcan
        </section>

        <details class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-red-200">
            <summary class="cursor-pointer font-black text-red-800">Report a safety concern</summary>
            <p class="mt-3 text-sm text-slate-600">Reports create an admin review case. They do not automatically prove a violation or change an account.</p>
            <form class="mt-5 grid gap-4" method="POST" action="{{ route('jobs.reports.store', $job) }}">@csrf
                <label class="grid gap-2 font-semibold">Category<select class="rounded-lg border-slate-300" name="category" required>@foreach(App\Enums\ReportCategory::cases() as $category)<option value="{{ $category->value }}">{{ str($category->value)->replace('_', ' ')->title() }}</option>@endforeach</select>@error('category')<span class="text-sm text-red-700">{{ $message }}</span>@enderror</label>
                <label class="grid gap-2 font-semibold">What happened?<textarea class="rounded-lg border-slate-300" name="description" rows="4" maxlength="3000" required>{{ old('description') }}</textarea>@error('description')<span class="text-sm text-red-700">{{ $message }}</span>@enderror</label>
                <button class="justify-self-start rounded-lg bg-red-700 px-5 py-3 font-bold text-white" type="submit">Submit safety report</button>
            </form>
        </details>

        @if($job->status === App\Enums\JobStatus::Completed || $job->reviews->isNotEmpty())
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-xl font-black">Participant reviews</h2>
            <div class="mt-5 grid gap-4">
                @foreach($job->reviews as $review)<article class="rounded-xl bg-slate-50 p-4"><div class="flex flex-wrap justify-between gap-2"><p class="font-bold">{{ $review->reviewer->name }} reviewed {{ $review->reviewee->name }}</p><p class="font-black text-amber-600">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span></p></div>@if($review->comment)<p class="mt-2 whitespace-pre-line text-slate-700">{{ $review->comment }}</p>@endif</article>@endforeach
            </div>
            @can('create', [App\Models\Review::class, $job])
                <form class="mt-6 grid gap-4 border-t border-slate-200 pt-6" method="POST" action="{{ route('jobs.reviews.store', $job) }}">@csrf
                    <label class="grid gap-2 font-semibold">Rating<select class="rounded-lg border-slate-300" name="rating" required>@foreach(range(5, 1) as $rating)<option value="{{ $rating }}">{{ $rating }} — {{ str_repeat('★', $rating) }}</option>@endforeach</select>@error('rating')<span class="text-sm text-red-700">{{ $message }}</span>@enderror</label>
                    <label class="grid gap-2 font-semibold">Comment <span class="text-sm font-normal text-slate-500">Optional</span><textarea class="rounded-lg border-slate-300" name="comment" rows="4" maxlength="2000">{{ old('comment') }}</textarea>@error('comment')<span class="text-sm text-red-700">{{ $message }}</span>@enderror</label>
                    <button class="justify-self-start rounded-lg bg-navy-900 px-5 py-3 font-bold text-white">Submit review</button>
                </form>
            @endcan
        </section>
        @endif

        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-xl font-black">Status history</h2>
            <ol class="mt-5 grid gap-4">
                @foreach($job->statusLogs as $log)
                    <li class="border-l-4 border-gold-400 pl-4"><p class="font-bold">{{ $log->from_status ? str($log->from_status->value)->replace('_', ' ')->title().' → ' : '' }}{{ str($log->to_status->value)->replace('_', ' ')->title() }}</p><p class="text-sm text-slate-600">{{ $log->changedBy->name }} · {{ $log->created_at->format('M j, Y g:i A') }}</p>@if($log->notes)<p class="mt-1 text-slate-700">{{ $log->notes }}</p>@endif</li>
                @endforeach
            </ol>
        </section>
    </div>
</x-layouts.app>
