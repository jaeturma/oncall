<x-layouts.app title="Safety cases" eyebrow="Account standing" description="Enforcement cases opened against your account, with the action taken and your appeal options.">
    @if($cases->isEmpty())
        <x-empty-state icon="shield-check" title="No enforcement cases" message="No safety or conduct cases affect your account. Keep bookings and communication on Oncall to stay in good standing." />
    @else
        <div class="card">
            <ul class="stack-list">
                @foreach($cases as $case)
                    <li>
                        <a class="flex flex-col gap-3 px-5 py-4 hover:bg-surface-muted sm:flex-row sm:items-center sm:justify-between sm:px-6" href="{{ route('enforcement-cases.show', $case) }}">
                            <span class="min-w-0">
                                <span class="block font-semibold text-ink">{{ str($case->violation_category->value)->replace('_', ' ')->lower()->ucfirst() }}</span>
                                <span class="mt-0.5 block text-sm text-ink-muted">Action: {{ $case->action ? str($case->action->value)->replace('_', ' ')->lower()->ucfirst() : 'Awaiting admin review' }} &middot; opened {{ $case->created_at->format('M j, Y') }}</span>
                            </span>
                            <span class="flex items-center gap-3"><x-ui.status-badge :status="$case->status" /><x-ui.icon name="chevron-right" class="size-4 text-ink-muted" /></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        @if($cases->hasPages())<div class="mt-6">{{ $cases->links() }}</div>@endif
    @endif
</x-layouts.app>
