@php
    $peso = fn ($amount): string => '₱'.number_format((float) $amount, 2);
    $me = auth()->user();
    $isFinder = $me->id === $job->service_finder_id;
    $transitions = $job->allowedTransitionsFor($me);
    $statusLabels = [
        'ON_THE_WAY' => 'On the way',
        'IN_PROGRESS' => 'Work in progress',
        'COMPLETED' => 'Completed',
        'CANCELLED' => 'Cancelled',
    ];
@endphp

<x-layouts.app :title="$job->serviceRequest->title" :eyebrow="'Booking · '.$job->serviceRequest->service->name">
    <x-slot:actions><x-ui.status-badge :status="$job->status" class="px-3 py-1.5 text-sm" /></x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
        <div class="grid content-start gap-6">
            {{-- Contacts --}}
            @can('revealContact', $job)
                <section class="card card-pad ring-gold-300">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="h3">Booking contacts</h2>
                            <p class="mt-1 text-sm text-ink-secondary">Shared only between the two of you because this booking is confirmed.</p>
                        </div>
                        <x-ui.icon name="lock-closed" class="size-5 text-gold-700" />
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach([['label' => 'Customer', 'user' => $job->serviceFinder], ['label' => 'Provider', 'user' => $job->provider]] as $party)
                            <div class="flex gap-3 rounded-xl border border-line p-4">
                                <x-ui.avatar :name="$party['user']->name" size="md" />
                                <div class="min-w-0 text-sm">
                                    <p class="text-xs font-semibold text-ink-muted uppercase">{{ $party['label'] }}</p>
                                    <p class="truncate font-semibold text-ink">{{ $party['user']->name }} @if($party['user']->id === $me->id)<span class="font-normal text-ink-muted">(you)</span>@endif</p>
                                    <x-ui.rating :value="$party['user']->rating_cached" />
                                    <p class="mt-1.5 flex items-center gap-1.5 text-ink-secondary"><x-ui.icon name="envelope" class="size-4 text-ink-muted" /><a class="truncate hover:underline" href="mailto:{{ $party['user']->email }}">{{ $party['user']->email }}</a></p>
                                    @if($party['user']->phone)<p class="mt-1 flex items-center gap-1.5 text-ink-secondary"><x-ui.icon name="phone" class="size-4 text-ink-muted" /><a class="hover:underline" href="tel:{{ $party['user']->phone }}">{{ $party['user']->phone }}</a></p>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @else
                <x-ui.alert tone="warning" title="Contact reveal is temporarily restricted for this account">Keep all booking communication on Oncall using the booking record below.</x-ui.alert>
            @endcan

            {{-- Status update --}}
            @if($transitions !== [])
                <section class="card card-pad">
                    <h2 class="h3">Update the booking status</h2>
                    <p class="mt-1 text-sm text-ink-secondary">Every change is recorded with your name and time.</p>
                    <form class="mt-4 grid gap-4" method="POST" action="{{ route('jobs.status.update', $job) }}">
                        @csrf @method('PATCH')
                        <x-form.select name="status" label="New status" required>
                            @foreach($transitions as $status)<option value="{{ $status->value }}">{{ $statusLabels[$status->value] ?? str($status->value)->replace('_', ' ')->lower()->ucfirst() }}</option>@endforeach
                        </x-form.select>
                        <x-form.textarea name="notes" label="Notes" rows="3" maxlength="2000" optional hint="Kept in the booking's audit log." />
                        <div><x-ui.button variant="dark" data-loading-text="Saving…">Record status change</x-ui.button></div>
                    </form>
                </section>
            @endif

            {{-- Payment --}}
            @if($job->jobPayment)
                @php($payment = $job->jobPayment)
                <section class="card card-pad">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <h2 class="h3">Payment</h2>
                        <x-ui.status-badge :status="$payment->status" />
                    </div>
                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                        <div><dt class="text-ink-muted">Agreed price</dt><dd class="mt-0.5 text-lg font-semibold text-ink tabular-nums">{{ $peso($payment->gross_amount) }}</dd></div>
                        <div><dt class="text-ink-muted">Oncall platform fee</dt><dd class="mt-0.5 text-lg font-semibold text-ink tabular-nums">{{ $peso($payment->platform_fee) }}</dd></div>
                        <div><dt class="text-ink-muted">Provider receives</dt><dd class="mt-0.5 text-lg font-bold text-navy-900 tabular-nums">{{ $peso($payment->net_amount) }}</dd></div>
                    </dl>
                    <p class="mt-4 rounded-lg bg-surface-muted p-3 text-sm text-ink-secondary">
                        @switch($payment->status)
                            @case(App\Enums\JobPaymentStatus::Pending) Waiting for the customer to confirm payment was made. @break
                            @case(App\Enums\JobPaymentStatus::Paid) Payment confirmed. The provider's earning is pending release by Oncall. @break
                            @case(App\Enums\JobPaymentStatus::Released) The provider's earning has been released to their wallet. @break
                            @case(App\Enums\JobPaymentStatus::Reversed) This earning was reversed. @break
                        @endswitch
                    </p>

                    @can('confirm', $payment)
                        <form class="mt-5 grid gap-4 border-t border-line pt-5 sm:grid-cols-2" method="POST" action="{{ route('job-payments.confirm', $payment) }}">
                            @csrf @method('PATCH')
                            <p class="text-sm text-ink-secondary sm:col-span-2">Confirm you paid the provider <span class="font-semibold text-ink">{{ $peso($payment->gross_amount) }}</span> for this job. Oncall records the payment; it does not process it.</p>
                            <x-form.select name="payment_method" label="How you paid" required>
                                <option value="Cash">Cash</option><option value="GCash">GCash</option><option value="Maya">Maya</option><option value="Bank transfer">Bank transfer</option>
                            </x-form.select>
                            <x-form.input name="payment_reference" label="Reference" maxlength="120" placeholder="Receipt no., transaction ID, or 'paid in cash on site'" required />
                            <div class="sm:col-span-2"><x-ui.button variant="primary" data-loading-text="Confirming…">Confirm payment made</x-ui.button></div>
                        </form>
                    @elseif($payment->status === App\Enums\JobPaymentStatus::Pending)
                        <p class="mt-3 text-sm text-ink-muted">The customer confirms payment from this page once the job is done.</p>
                    @endcan
                </section>
            @endif

            {{-- Booking record / messages --}}
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="h3">Booking record</h2>
                        <p class="mt-0.5 text-sm text-ink-secondary">Messages, agreements, and evidence stay with this booking.</p>
                    </div>
                    <x-ui.badge tone="accent" icon="shield-check">On record</x-ui.badge>
                </div>
                <div class="grid gap-3 p-5 sm:p-6">
                    @forelse($job->messages as $message)
                        @php($mine = $message->sender_id === $me->id)
                        <article class="flex gap-3 {{ $mine ? 'flex-row-reverse' : '' }}">
                            <x-ui.avatar :name="$message->sender->name" size="sm" :tone="$mine ? 'accent' : 'light'" />
                            <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm {{ $mine ? 'rounded-tr-sm bg-navy-900 text-white' : 'rounded-tl-sm bg-surface-muted text-ink' }}">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs {{ $mine ? 'text-white/70' : 'text-ink-muted' }}">
                                    <span class="font-semibold {{ $mine ? 'text-white' : 'text-ink' }}">{{ $mine ? 'You' : $message->sender->name }}</span>
                                    @if($message->type !== App\Enums\JobMessageType::Message)<span class="rounded-full px-1.5 py-0.5 font-semibold uppercase tracking-wide {{ $mine ? 'bg-white/15' : 'bg-navy-50 text-navy-800' }}">{{ str($message->type->value)->lower() }}</span>@endif
                                    <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('M j, g:i A') }}</time>
                                </div>
                                <p class="mt-1 whitespace-pre-line">{{ $message->body }}</p>
                            </div>
                        </article>
                    @empty
                        <p class="rounded-xl bg-surface-muted p-4 text-center text-sm text-ink-secondary">No messages yet. Use this record for anything you agree on.</p>
                    @endforelse
                </div>
                @can('create', [App\Models\JobMessage::class, $job])
                    <form class="grid gap-4 border-t border-line p-5 sm:p-6" method="POST" action="{{ route('jobs.messages.store', $job) }}">
                        @csrf
                        <x-form.textarea name="body" label="Add to the record" rows="3" maxlength="5000" placeholder="Write a message, confirm an agreement, or note evidence…" required />
                        <div class="flex flex-wrap items-end gap-3">
                            <x-form.select name="type" label="Record as" class="w-44">
                                @foreach(App\Enums\JobMessageType::cases() as $type)<option value="{{ $type->value }}" @selected(old('type', 'MESSAGE') === $type->value)>{{ str($type->value)->lower()->ucfirst() }}</option>@endforeach
                            </x-form.select>
                            <x-ui.button variant="dark" data-loading-text="Sending…">Send</x-ui.button>
                        </div>
                    </form>
                @else
                    <p class="border-t border-line p-5 text-sm text-warning-800 sm:p-6">Messaging is temporarily restricted for this account.</p>
                @endcan
            </section>

            {{-- Reviews --}}
            @if($job->status === App\Enums\JobStatus::Completed || $job->reviews->isNotEmpty())
                <section class="card card-pad">
                    <h2 class="h3">Reviews</h2>
                    @if($job->reviews->isNotEmpty())
                        <ul class="mt-4 grid gap-3">
                            @foreach($job->reviews as $review)
                                <li class="rounded-xl bg-surface-muted p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                        <p class="font-semibold text-ink">{{ $review->reviewer->name }} <span class="font-normal text-ink-muted">reviewed</span> {{ $review->reviewee->name }}</p>
                                        <span class="text-gold-500" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                                    </div>
                                    @if($review->comment)<p class="mt-2 whitespace-pre-line text-sm text-ink-secondary">{{ $review->comment }}</p>@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @can('create', [App\Models\Review::class, $job])
                        <form class="mt-5 grid gap-4 border-t border-line pt-5" method="POST" action="{{ route('jobs.reviews.store', $job) }}">
                            @csrf
                            <fieldset class="grid gap-2">
                                <legend class="field-label">Your rating <span class="required-mark" aria-hidden="true">*</span></legend>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(range(5, 1) as $rating)
                                        <label class="choice items-center gap-2 px-3 py-2">
                                            <input class="radio" type="radio" name="rating" value="{{ $rating }}" @checked((int) old('rating') === $rating) required>
                                            <span class="text-sm font-semibold text-gold-600">{{ str_repeat('★', $rating) }}</span>
                                            <span class="sr-only">{{ $rating }} out of 5</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('rating')<p class="field-error"><x-ui.icon name="exclamation-triangle" class="mt-0.5 size-4" />{{ $message }}</p>@enderror
                            </fieldset>
                            <x-form.textarea name="comment" label="Comment" rows="3" maxlength="2000" optional placeholder="What went well? What could be better?" />
                            <div><x-ui.button variant="dark" data-loading-text="Submitting…">Submit review</x-ui.button></div>
                        </form>
                    @endcan
                </section>
            @endif

            {{-- Dispute --}}
            @if($job->dispute)
                @php($dispute = $job->dispute)
                <section class="card card-pad ring-danger-100">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <h2 class="h3 text-danger-800">Dispute</h2>
                        <x-ui.status-badge :status="$dispute->status" />
                    </div>
                    <p class="mt-2 text-sm text-ink-secondary">{{ str($dispute->category->value)->replace('_', ' ')->lower()->ucfirst() }} &middot; raised by {{ $dispute->raised_by === $me->id ? 'you' : ($dispute->raised_by === $job->service_finder_id ? 'the customer' : 'the provider') }}</p>
                    <p class="mt-3 whitespace-pre-line text-sm text-ink">{{ $dispute->description }}</p>
                    @if($dispute->resolution)<p class="mt-3 rounded-lg bg-surface-muted p-3 text-sm"><span class="font-semibold">Resolution:</span> {{ $dispute->resolution }}</p>@endif
                    @can('withdraw', $dispute)
                        <form class="mt-4" method="POST" action="{{ route('disputes.withdraw', $dispute) }}">@csrf @method('PATCH')<x-ui.button variant="secondary" size="sm" data-loading-text="Withdrawing…">Withdraw dispute</x-ui.button></form>
                    @endcan
                </section>
            @elseif(Illuminate\Support\Facades\Gate::allows('create', [App\Models\Dispute::class, $job]))
                <details class="card group" @if($errors->has('category') || $errors->has('description')) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-5 sm:p-6">
                        <span><span class="block font-semibold text-ink">Something went wrong? Raise a dispute</span><span class="mt-0.5 block text-sm text-ink-secondary">An admin reviews it and the job payment is frozen until it's resolved.</span></span>
                        <x-ui.icon name="chevron-down" class="size-5 text-ink-muted transition group-open:rotate-180" />
                    </summary>
                    <form class="grid gap-4 border-t border-line p-5 sm:p-6" method="POST" action="{{ route('disputes.store', $job) }}">
                        @csrf
                        <x-form.select name="category" label="What went wrong?" required>
                            @foreach(App\Enums\DisputeCategory::cases() as $category)<option value="{{ $category->value }}">{{ str($category->value)->replace('_', ' ')->lower()->ucfirst() }}</option>@endforeach
                        </x-form.select>
                        <x-form.textarea name="description" label="Describe what happened" rows="4" minlength="20" maxlength="3000" required hint="At least 20 characters. Be specific: dates, amounts, what was agreed." />
                        <div><x-ui.button variant="danger" data-loading-text="Opening…">Open dispute</x-ui.button></div>
                    </form>
                </details>
            @endif

            {{-- Safety report --}}
            <details class="card group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-5 sm:p-6">
                    <span class="flex items-center gap-3"><x-ui.icon name="flag" class="size-5 text-danger-600" /><span><span class="block font-semibold text-ink">Report a safety concern</span><span class="mt-0.5 block text-sm text-ink-secondary">Creates a private admin review case. It does not automatically change any account.</span></span></span>
                    <x-ui.icon name="chevron-down" class="size-5 text-ink-muted transition group-open:rotate-180" />
                </summary>
                <form class="grid gap-4 border-t border-line p-5 sm:p-6" method="POST" action="{{ route('jobs.reports.store', $job) }}">
                    @csrf
                    <x-form.select name="category" label="Category" required>
                        @foreach(App\Enums\ReportCategory::cases() as $category)<option value="{{ $category->value }}">{{ str($category->value)->replace('_', ' ')->lower()->ucfirst() }}</option>@endforeach
                    </x-form.select>
                    <x-form.textarea name="description" label="What happened?" rows="4" maxlength="3000" required />
                    <div><x-ui.button variant="danger-solid" data-loading-text="Submitting…">Submit safety report</x-ui.button></div>
                </form>
            </details>
        </div>

        {{-- Sidebar --}}
        <aside class="grid content-start gap-4">
            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-ink">Summary</h2>
                <dl class="mt-3 grid gap-3 text-sm">
                    <div class="flex items-start justify-between gap-3"><dt class="text-ink-muted">Agreed price</dt><dd class="font-semibold text-ink tabular-nums">{{ $peso($job->agreed_price) }}</dd></div>
                    <div class="flex items-start justify-between gap-3"><dt class="text-ink-muted">Location</dt><dd class="text-right font-medium text-ink">{{ $job->serviceRequest->municipality?->name }}, {{ $job->serviceRequest->province->name }}</dd></div>
                    <div class="flex items-start justify-between gap-3"><dt class="text-ink-muted">Booked</dt><dd class="text-right font-medium text-ink">{{ $job->created_at->format('M j, Y') }}</dd></div>
                    <div class="flex items-start justify-between gap-3"><dt class="text-ink-muted">Reference</dt><dd class="font-medium text-ink">#{{ $job->id }}</dd></div>
                </dl>
                <a class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-navy-800 hover:underline" href="{{ route('service-requests.show', $job->serviceRequest) }}">View original request <x-ui.icon name="arrow-right" class="size-4" /></a>
            </div>

            <section class="card card-pad">
                <h2 class="text-sm font-semibold text-ink">Status history</h2>
                <ol class="mt-3 grid gap-3 border-l-2 border-line pl-4 text-sm">
                    @foreach($job->statusLogs as $log)
                        <li class="relative">
                            <span class="absolute top-1.5 -left-[1.4rem] size-2.5 rounded-full {{ $loop->first ? 'bg-gold-500' : 'bg-line-strong' }}" aria-hidden="true"></span>
                            <p class="font-medium text-ink">{{ str($log->to_status->value)->replace('_', ' ')->lower()->ucfirst() }}</p>
                            <p class="text-xs text-ink-muted">{{ $log->changedBy->name }} &middot; {{ $log->created_at->format('M j, g:i A') }}</p>
                            @if($log->notes)<p class="mt-1 text-ink-secondary">{{ $log->notes }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </section>

            <x-safety-notice variant="compact" />
        </aside>
    </div>
</x-layouts.app>
