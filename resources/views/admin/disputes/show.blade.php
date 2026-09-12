@php($peso = fn ($amount): string => '₱'.number_format((float) $amount, 2))

<x-layouts.admin :title="$dispute->job->serviceRequest->title" :description="str($dispute->category->value)->replace('_', ' ')->lower()->ucfirst().' · opened '.$dispute->created_at->format('M j, Y')">
    <x-slot:actions><x-ui.status-badge :status="$dispute->status" class="px-3 py-1.5 text-sm" /></x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="grid content-start gap-6">
            <section class="card card-pad">
                <h2 class="h3">Dispute</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">Raised by</dt><dd class="mt-0.5 font-medium text-ink">{{ $dispute->raisedBy->name }} <span class="text-ink-muted">({{ $dispute->raised_by === $dispute->job->service_finder_id ? 'customer' : 'provider' }})</span></dd></div>
                    <div><dt class="text-ink-muted">Against</dt><dd class="mt-0.5 font-medium text-ink">{{ $dispute->againstUser->name }}</dd></div>
                    <div><dt class="text-ink-muted">Agreed price</dt><dd class="mt-0.5 font-medium text-ink tabular-nums">{{ $peso($dispute->job->agreed_price) }}</dd></div>
                    <div><dt class="text-ink-muted">Payment</dt><dd class="mt-0.5 font-medium text-ink">@if($dispute->job->jobPayment)<x-ui.status-badge :status="$dispute->job->jobPayment->status" /> <span class="text-ink-secondary">provider net {{ $peso($dispute->job->jobPayment->net_amount) }}</span>@else<span class="text-ink-muted">No payment record</span>@endif</dd></div>
                </dl>
                <p class="mt-5 whitespace-pre-line rounded-lg bg-surface-muted p-4 text-sm text-ink">{{ $dispute->description }}</p>
                @if($dispute->enforcementCase)<a class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-navy-800 hover:underline" href="{{ route('admin.enforcement.show', $dispute->enforcementCase) }}">Linked enforcement case <x-ui.icon name="arrow-right" class="size-4" /></a>@endif
            </section>

            @if($dispute->isOpen())
                <form class="card card-pad grid gap-5" method="POST" action="{{ route('admin.disputes.update', $dispute) }}">
                    @csrf @method('PATCH')
                    <h2 class="h3">Resolve</h2>
                    <x-form.errors />
                    <x-form.select name="action" label="Decision" required>
                        @if($dispute->status === App\Enums\DisputeStatus::Open)<option value="start_review">Move to review only (no payment change)</option>@endif
                        <option value="reject">Reject — job stands, payment proceeds normally</option>
                        <option value="uphold">Uphold — reverse the whole payment, cancel the job</option>
                        <option value="partial">Partial — reduce the provider's earning by a refund amount</option>
                    </x-form.select>
                    <x-form.input name="refund_amount" type="number" label="Refund amount (₱)" min="0.01" step="0.01" inputmode="decimal" hint="Partial decisions only." class="sm:max-w-xs" />
                    <x-form.textarea name="resolution" label="Resolution notes" rows="3" hint="Shown to both parties. Required unless 'review only'." />
                    <fieldset class="grid gap-3 rounded-xl border border-line p-4">
                        <legend class="px-1 text-sm font-semibold text-ink">Enforcement</legend>
                        <x-form.checkbox name="open_enforcement" label="Also open an enforcement case" :checked="(bool) old('open_enforcement')" />
                        <x-form.select name="enforce_against" label="Against">
                            <option value="respondent">{{ $dispute->againstUser->name }} (respondent)</option>
                            <option value="raiser">{{ $dispute->raisedBy->name }} (raiser)</option>
                        </x-form.select>
                    </fieldset>
                    <div><x-ui.button variant="primary" data-loading-text="Applying…">Apply decision</x-ui.button></div>
                </form>
            @else
                <x-ui.alert tone="info" :title="'Resolved as '.str($dispute->status->value)->replace('_', ' ')->lower()">By {{ $dispute->resolver?->name }} on {{ $dispute->resolved_at?->format('M j, Y') }}.@if($dispute->resolution) <span class="mt-1 block">{{ $dispute->resolution }}</span>@endif</x-ui.alert>
            @endif
        </div>

        <aside class="card card-pad text-sm">
            <p class="font-semibold text-ink">What each decision does</p>
            <ul class="mt-3 grid gap-2.5 text-ink-secondary">
                <li><span class="font-medium text-ink">Reject:</span> the job stands and its payment proceeds normally.</li>
                <li><span class="font-medium text-ink">Uphold:</span> the whole payment is reversed and the job is cancelled.</li>
                <li><span class="font-medium text-ink">Partial:</span> the provider's earning is reduced by the refund amount.</li>
            </ul>
        </aside>
    </div>
</x-layouts.admin>
