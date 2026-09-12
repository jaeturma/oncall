<x-layouts.app :title="str($case->violation_category->value)->replace('_', ' ')->lower()->ucfirst()" eyebrow="Safety case">
    <x-slot:actions><x-ui.status-badge :status="$case->status" class="px-3 py-1.5 text-sm" /></x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <div class="grid content-start gap-6">
            <section class="card card-pad">
                <h2 class="h3">Case details</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">Status</dt><dd class="mt-1"><x-ui.status-badge :status="$case->status" /></dd></div>
                    <div><dt class="text-ink-muted">Action taken</dt><dd class="mt-0.5 font-medium text-ink">{{ $case->action ? str($case->action->value)->replace('_', ' ')->lower()->ucfirst() : 'Awaiting admin review' }}</dd></div>
                    <div><dt class="text-ink-muted">Appeal</dt><dd class="mt-1"><x-ui.status-badge :status="$case->appeal_status" /></dd></div>
                    @if($case->ends_at)<div><dt class="text-ink-muted">Ends</dt><dd class="mt-0.5 font-medium text-ink">{{ $case->ends_at->format('M j, Y · g:i A') }}</dd></div>@endif
                    @if($case->restricted_capabilities)
                        <div class="sm:col-span-2"><dt class="text-ink-muted">Restricted capabilities</dt><dd class="mt-1 flex flex-wrap gap-1.5">@foreach($case->restricted_capabilities as $capability)<span class="badge badge-danger">{{ str($capability)->replace('_', ' ')->lower()->ucfirst() }}</span>@endforeach</dd></div>
                    @endif
                </dl>
                @if($case->resolution)
                    <div class="mt-5 border-t border-line pt-5"><h3 class="text-sm font-semibold text-ink">Admin notes</h3><p class="mt-1.5 whitespace-pre-line text-sm text-ink-secondary">{{ $case->resolution }}</p></div>
                @endif
            </section>

            @if($case->action && $case->appeal_status === App\Enums\AppealStatus::None)
                <form class="card card-pad grid gap-4" method="POST" action="{{ route('enforcement-cases.appeal', $case) }}">
                    @csrf
                    <div><h2 class="h3">Appeal this decision</h2><p class="mt-1 text-sm text-ink-secondary">Explain what happened from your side. An admin will review your appeal and the original case together.</p></div>
                    <x-form.textarea name="appeal_reason" label="Your explanation" rows="5" required />
                    <div><x-ui.button variant="dark" data-loading-text="Submitting…">Submit appeal</x-ui.button></div>
                </form>
            @elseif($case->appeal_status !== App\Enums\AppealStatus::None)
                <x-ui.alert tone="info" title="Appeal {{ str($case->appeal_status->value)->replace('_', ' ')->lower() }}">Your appeal has been recorded. You'll be notified when an admin reaches a decision.</x-ui.alert>
            @endif
        </div>

        <aside class="grid content-start gap-4">
            <div class="card card-pad text-sm">
                <p class="font-semibold text-ink">How enforcement works</p>
                <p class="mt-2 text-ink-secondary">Reports and disputes are reviewed privately by Oncall admins. Depending on severity, an account may receive a warning, be placed under review, be temporarily restricted, or be suspended. You can appeal any action once.</p>
            </div>
            <x-safety-notice variant="compact" />
        </aside>
    </div>
</x-layouts.app>
