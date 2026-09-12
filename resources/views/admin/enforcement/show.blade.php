<x-layouts.admin :title="'Case · '.$case->user->name" :description="str($case->violation_category->value)->replace('_', ' ')->lower()->ucfirst().' · '.str($case->severity->value)->lower()->ucfirst().' severity'">
    <x-slot:actions><x-ui.status-badge :status="$case->status" class="px-3 py-1.5 text-sm" /></x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="grid content-start gap-6">
            <section class="card card-pad">
                <h2 class="h3">Case record</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">Account</dt><dd class="mt-0.5 font-medium text-ink">{{ $case->user->name }} <span class="text-ink-muted">({{ $case->user->email }})</span></dd></div>
                    <div><dt class="text-ink-muted">Current action</dt><dd class="mt-0.5 font-medium text-ink">{{ $case->action ? str($case->action->value)->replace('_', ' ')->lower()->ucfirst() : 'None yet' }}</dd></div>
                    <div><dt class="text-ink-muted">Appeal</dt><dd class="mt-1"><x-ui.status-badge :status="$case->appeal_status" /></dd></div>
                    @if($case->ends_at)<div><dt class="text-ink-muted">Restriction ends</dt><dd class="mt-0.5 font-medium text-ink">{{ $case->ends_at->format('M j, Y · g:i A') }}</dd></div>@endif
                    @if($case->restricted_capabilities)<div class="sm:col-span-2"><dt class="text-ink-muted">Restricted capabilities</dt><dd class="mt-1 flex flex-wrap gap-1.5">@foreach($case->restricted_capabilities as $capability)<span class="badge badge-danger">{{ str($capability)->replace('_', ' ')->lower()->ucfirst() }}</span>@endforeach</dd></div>@endif
                </dl>
                @if($case->relatedReport)
                    <div class="mt-5 border-t border-line pt-5">
                        <h3 class="text-sm font-semibold text-ink">Report by {{ $case->relatedReport->reporter?->name ?? 'system' }}</h3>
                        <p class="mt-1.5 whitespace-pre-line rounded-lg bg-surface-muted p-3 text-sm text-ink">{{ $case->relatedReport->description }}</p>
                    </div>
                @endif
                @if($case->appeal_reason)
                    <div class="mt-5 border-t border-line pt-5">
                        <h3 class="text-sm font-semibold text-ink">Appeal from the account holder</h3>
                        <p class="mt-1.5 whitespace-pre-line rounded-lg bg-warning-50 p-3 text-sm text-warning-800">{{ $case->appeal_reason }}</p>
                    </div>
                @endif
                @if($case->resolution)
                    <div class="mt-5 border-t border-line pt-5"><h3 class="text-sm font-semibold text-ink">Admin notes</h3><p class="mt-1.5 whitespace-pre-line text-sm text-ink-secondary">{{ $case->resolution }}</p></div>
                @endif
            </section>

            @if($nextAction)
                <form class="card card-pad grid gap-5 ring-warning-100" method="POST" action="{{ route('admin.enforcement.update', $case) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="{{ $nextAction->value }}">
                    <div>
                        <h2 class="h3">Apply next step: {{ str($nextAction->value)->replace('_', ' ')->lower()->ucfirst() }}</h2>
                        <p class="mt-1 text-sm text-ink-secondary">Enforcement escalates one step at a time. Each step is logged with your name.</p>
                    </div>
                    <x-form.errors />
                    <x-form.select name="severity" label="Severity">
                        @foreach(App\Enums\ViolationSeverity::cases() as $severity)<option value="{{ $severity->value }}" @selected(old('severity', $case->severity->value) === $severity->value)>{{ str($severity->value)->lower()->ucfirst() }}</option>@endforeach
                    </x-form.select>
                    @if($nextAction === App\Enums\EnforcementAction::TemporaryRestriction)
                        <fieldset class="grid gap-2">
                            <legend class="field-label">Targeted restrictions</legend>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach(App\Enums\RestrictedCapability::cases() as $capability)
                                    <label class="choice items-center py-2.5"><input class="checkbox" type="checkbox" name="restricted_capabilities[]" value="{{ $capability->value }}" @checked(in_array($capability->value, old('restricted_capabilities', [])))><span class="text-sm font-medium text-ink">{{ str($capability->value)->replace('_', ' ')->lower()->ucfirst() }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>
                        <x-form.input name="duration_days" type="number" label="Duration in days" min="1" max="365" inputmode="numeric" required class="sm:max-w-xs" />
                    @endif
                    <x-form.textarea name="resolution" label="Review notes" rows="3" hint="Visible to the account holder on their case page." />
                    @if($case->appeal_status === App\Enums\AppealStatus::Requested)
                        <x-form.select name="appeal_status" label="Appeal decision">
                            <option value="UNDER_REVIEW">Under review</option><option value="APPROVED">Approved</option><option value="DENIED">Denied</option>
                        </x-form.select>
                    @endif
                    <div><x-ui.button variant="danger-solid" data-loading-text="Applying…">Apply reviewed action</x-ui.button></div>
                </form>
            @endif

            <form class="card card-pad grid gap-4" method="POST" action="{{ route('admin.enforcement.resolve', $case) }}">
                @csrf @method('PATCH')
                <div><h2 class="h3">Resolve and close</h2><p class="mt-1 text-sm text-ink-secondary">Lifts any active restriction and closes the case with a written resolution.</p></div>
                <x-form.textarea name="resolution" label="Resolution" rows="3" required />
                <div><x-ui.button variant="dark" data-loading-text="Resolving…">Resolve case</x-ui.button></div>
            </form>
        </div>

        <aside class="grid content-start gap-4">
            <div class="card card-pad text-sm">
                <p class="font-semibold text-ink">Escalation ladder</p>
                <ol class="mt-3 grid gap-2">
                    @foreach(App\Enums\EnforcementAction::cases() as $index => $action)
                        <li class="flex items-center gap-2 {{ $case->action === $action ? 'font-semibold text-ink' : 'text-ink-secondary' }}"><span class="flex size-5 items-center justify-center rounded-full text-[11px] font-bold {{ $case->action === $action ? 'bg-navy-900 text-white' : 'bg-navy-50 text-navy-800' }}">{{ $index + 1 }}</span>{{ str($action->value)->replace('_', ' ')->lower()->ucfirst() }}</li>
                    @endforeach
                </ol>
            </div>
        </aside>
    </div>
</x-layouts.admin>
