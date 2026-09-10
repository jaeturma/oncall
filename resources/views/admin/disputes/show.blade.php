<x-layouts.admin title="Review dispute">
    <div class="grid gap-6">
        <x-flash />

        <article class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-black">{{ $dispute->job->serviceRequest->title }}</h1>
                    <p class="mt-1 text-slate-600">{{ str($dispute->category->value)->replace('_', ' ')->title() }} &middot; opened {{ $dispute->created_at->format('M j, Y') }}</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ str($dispute->status->value)->replace('_', ' ')->title() }}</span>
            </div>
            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="font-bold">Raised by</dt><dd>{{ $dispute->raisedBy->name }} ({{ $dispute->raised_by === $dispute->job->service_finder_id ? 'Service Finder' : 'Provider' }})</dd></div>
                <div><dt class="font-bold">Against</dt><dd>{{ $dispute->againstUser->name }}</dd></div>
                <div><dt class="font-bold">Agreed price</dt><dd>PHP {{ number_format((float) $dispute->job->agreed_price, 2) }}</dd></div>
                <div><dt class="font-bold">Payment status</dt><dd>{{ $dispute->job->jobPayment ? str($dispute->job->jobPayment->status->value)->title().' (provider net PHP '.number_format((float) $dispute->job->jobPayment->net_amount, 2).')' : 'No payment record' }}</dd></div>
            </dl>
            <p class="mt-5 whitespace-pre-line rounded-xl bg-slate-50 p-4 text-slate-700">{{ $dispute->description }}</p>
            @if($dispute->enforcementCase)<p class="mt-3 text-sm"><a class="font-bold text-navy-800 hover:underline" href="{{ route('admin.enforcement.show', $dispute->enforcementCase) }}">Linked enforcement case &rarr;</a></p>@endif
        </article>

        @if($dispute->isOpen())
            <form class="grid gap-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200" method="POST" action="{{ route('admin.disputes.update', $dispute) }}">
                @csrf @method('PATCH')
                <h2 class="text-xl font-black">Resolve</h2>
                <label class="grid gap-2 font-semibold">Decision
                    <select class="rounded-lg border border-slate-300 p-3" name="action" required>
                        @if($dispute->status === App\Enums\DisputeStatus::Open)<option value="start_review">Move to review only (no payment change)</option>@endif
                        <option value="reject">Reject &mdash; job stands, payment proceeds normally</option>
                        <option value="uphold">Uphold &mdash; reverse the whole payment, cancel the job</option>
                        <option value="partial">Partial &mdash; reduce the provider's earning by a refund amount</option>
                    </select>
                    @error('action')<span class="text-sm font-normal text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="grid gap-2 font-semibold">Refund amount (PHP) &mdash; <span class="font-normal text-slate-500">Partial decision only</span>
                    <input class="rounded-lg border border-slate-300 p-3" type="number" name="refund_amount" min="0.01" step="0.01" value="{{ old('refund_amount') }}">
                    @error('refund_amount')<span class="text-sm font-normal text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="grid gap-2 font-semibold">Resolution notes <span class="font-normal text-slate-500">(shown to both parties; required unless "review only")</span>
                    <textarea class="rounded-lg border border-slate-300 p-3" name="resolution" rows="3">{{ old('resolution') }}</textarea>
                    @error('resolution')<span class="text-sm font-normal text-red-700">{{ $message }}</span>@enderror
                </label>
                <div class="grid gap-2">
                    <label class="flex items-center gap-2 font-semibold"><input type="checkbox" name="open_enforcement" value="1" @checked(old('open_enforcement'))> Also open an enforcement case</label>
                    <select class="w-64 rounded-lg border border-slate-300 p-2 text-sm" name="enforce_against">
                        <option value="respondent">Against {{ $dispute->againstUser->name }} (respondent)</option>
                        <option value="raiser">Against {{ $dispute->raisedBy->name }} (raiser)</option>
                    </select>
                    @error('enforce_against')<span class="text-sm font-normal text-red-700">{{ $message }}</span>@enderror
                </div>
                <button class="justify-self-start rounded-lg bg-gold-400 px-6 py-3 font-bold text-navy-900 hover:bg-gold-500">Apply</button>
            </form>
        @else
            <div class="rounded-2xl bg-white p-6 text-slate-600 shadow-sm ring-1 ring-slate-200">Resolved as <span class="font-bold">{{ str($dispute->status->value)->replace('_', ' ')->title() }}</span> by {{ $dispute->resolver?->name }} on {{ $dispute->resolved_at?->format('M j, Y') }}. @if($dispute->resolution)<span class="mt-2 block">{{ $dispute->resolution }}</span>@endif</div>
        @endif
    </div>
</x-layouts.admin>
