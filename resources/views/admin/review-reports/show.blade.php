@php
    $review = $report->review;
@endphp

<x-layouts.admin title="Review report" description="Resolving this report never by itself changes the review's visibility — use the moderation action below for that.">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="grid content-start gap-6">
            <section class="card card-pad">
                <h2 class="h3">Reported review</h2>
                <div class="mt-3 rounded-xl border border-line p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <p class="font-semibold text-ink">{{ $review->reviewer->name }} <span class="font-normal text-ink-muted">reviewed</span> {{ $review->reviewee->name }}</p>
                        <span class="text-gold-500" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                    </div>
                    @if($review->comment)<p class="mt-2 whitespace-pre-line text-sm text-ink-secondary">{{ $review->comment }}</p>@endif
                    <p class="mt-2 text-xs text-ink-muted">Status: {{ str($review->status->value)->title() }} &middot; <a class="underline" href="{{ route('admin.jobs.index') }}">Job #{{ $review->job_id }}</a></p>
                </div>

                <form class="mt-5 grid gap-4 border-t border-line pt-5 sm:grid-cols-2" method="POST" action="{{ route('admin.reviews.moderate', $review) }}">
                    @csrf @method('PATCH')
                    <x-form.select name="status" label="Review visibility" required>
                        <option value="PUBLISHED" @selected($review->status->value === 'PUBLISHED')>Published (keep visible)</option>
                        <option value="HIDDEN" @selected($review->status->value === 'HIDDEN')>Hidden</option>
                        <option value="REMOVED" @selected($review->status->value === 'REMOVED')>Removed</option>
                    </x-form.select>
                    <x-form.input name="notes" label="Moderation notes (internal)" wrapper-class="sm:col-span-2" optional hint="Never shown to users — audit trail only." />
                    <div class="sm:col-span-2"><x-ui.button variant="dark" data-loading-text="Saving…">Update review visibility</x-ui.button></div>
                </form>
            </section>

            <section class="card card-pad">
                <h2 class="h3">Report</h2>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">Reported by</dt><dd class="font-medium text-ink">{{ $report->reporter->name }}</dd></div>
                    <div><dt class="text-ink-muted">Category</dt><dd class="font-medium text-ink">{{ str($report->category->value)->replace('_', ' ')->title() }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-ink-muted">Description</dt><dd class="text-ink">{{ $report->description ?: '—' }}</dd></div>
                </dl>

                @if($report->status->value === 'SUBMITTED' || $report->status->value === 'UNDER_REVIEW')
                    <form class="mt-5 grid gap-4 border-t border-line pt-5" method="POST" action="{{ route('admin.review-reports.update', $report) }}">
                        @csrf @method('PATCH')
                        <x-form.select name="status" label="Resolution" required>
                            <option value="RESOLVED">Resolved — action taken</option>
                            <option value="DISMISSED">Dismissed — no action needed</option>
                        </x-form.select>
                        <x-form.textarea name="notes" label="Notes (internal)" rows="2" optional hint="Never shown to users — audit trail only." />
                        <div><x-ui.button variant="primary" data-loading-text="Saving…">Resolve report</x-ui.button></div>
                    </form>
                @else
                    <p class="mt-5 border-t border-line pt-5 text-sm text-ink-secondary">
                        Resolved <strong>{{ str($report->status->value)->title() }}</strong> by {{ $report->reviewer?->name ?? '—' }} on {{ $report->reviewed_at?->format('M j, Y') }}.
                        @if($report->moderation_notes)<span class="mt-1 block text-xs text-ink-muted">{{ $report->moderation_notes }}</span>@endif
                    </p>
                @endif
            </section>
        </div>

        <aside class="card card-pad content-start text-sm">
            <p class="font-semibold text-ink">Reminders</p>
            <ul class="mt-3 grid gap-2 text-ink-secondary">
                <li class="flex gap-2"><x-ui.icon name="eye-slash" class="mt-0.5 size-4 shrink-0 text-ink-muted" />The reporter's identity is never shown to the reviewed provider or the reviewer.</li>
                <li class="flex gap-2"><x-ui.icon name="lock-closed" class="mt-0.5 size-4 shrink-0 text-ink-muted" />Moderation notes stay internal — never rendered on any marketplace page.</li>
                <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 shrink-0 text-success-600" />Every moderation action here is written to the audit log.</li>
            </ul>
        </aside>
    </div>
</x-layouts.admin>
