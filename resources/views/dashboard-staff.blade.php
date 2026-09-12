@php
    $roleLabel = str($role->value)->lower()->ucfirst();
    $stepLabel = match ($role) {
        App\Enums\UserRole::Accounting => 'Accounting review',
        App\Enums\UserRole::Budget => 'Budget approval',
        App\Enums\UserRole::Cashier => 'Cashier disbursement',
        default => 'Review',
    };
@endphp

<x-layouts.app title="{{ $roleLabel }} overview" eyebrow="Staff" description="Withdrawals move through Accounting review, Budget approval, and Cashier disbursement. You can only act on the step assigned to your role.">
    <div class="grid gap-6">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.stat-card label="Waiting for you" :value="$metrics['withdrawals_for_me']" :hint="$stepLabel" icon="banknotes" tone="warning" :href="route('staff.withdrawals.index')" />
            <x-ui.stat-card label="Open withdrawals" :value="$metrics['withdrawals_open']" hint="All steps" icon="arrow-path" :href="route('staff.withdrawals.index')" />
            <x-ui.stat-card label="Payments to release" :value="$metrics['payments_awaiting_release']" hint="Customer confirmed" icon="document-check" tone="success" :href="route('staff.job-payments.index')" />
        </div>

        <section class="card">
            <div class="card-header">
                <h2 class="h3">Oldest in your queue</h2>
                <a class="text-sm font-semibold text-navy-800 hover:underline" href="{{ route('staff.withdrawals.index') }}">Open queue</a>
            </div>
            @if($queue->isEmpty())
                <div class="px-5 py-8 text-center sm:px-6"><p class="font-medium text-ink">Nothing waiting for you</p><p class="mt-1 text-sm text-ink-secondary">New withdrawals at your step will appear here.</p></div>
            @else
                <ul class="stack-list">
                    @foreach($queue as $withdrawal)
                        <li class="flex items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-ink">₱{{ number_format((float) $withdrawal->amount, 2) }} &middot; {{ $withdrawal->user->name }}</span>
                                <span class="block text-xs text-ink-muted">{{ $withdrawal->payout_method }} &middot; requested {{ $withdrawal->created_at->diffForHumans() }}</span>
                            </span>
                            <x-ui.status-badge :status="$withdrawal->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.app>
