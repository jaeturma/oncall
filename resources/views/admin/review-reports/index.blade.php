<x-layouts.admin title="Review reports" description="Reports never automatically hide a review — a moderator decides after reading the linked review.">
    <div class="mb-5 flex flex-wrap gap-2">
        @foreach(['SUBMITTED' => 'Open', 'UNDER_REVIEW' => 'Under review', 'RESOLVED' => 'Resolved', 'DISMISSED' => 'Dismissed'] as $value => $label)
            <x-ui.button :href="route('admin.review-reports.index', ['status' => $value])" :variant="$status->value === $value ? 'dark' : 'secondary'" size="sm">{{ $label }}</x-ui.button>
        @endforeach
    </div>

    @if($reports->isEmpty())
        <x-empty-state icon="flag" title="No reports here" message="No review reports match this filter." />
    @else
        <div class="card">
            <ul class="stack-list">
                @foreach($reports as $report)
                    <li>
                        <a class="flex flex-col gap-2 px-5 py-4 hover:bg-surface-muted sm:flex-row sm:items-center sm:justify-between sm:px-6" href="{{ route('admin.review-reports.show', $report) }}">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-ink">{{ str($report->category->value)->replace('_', ' ')->title() }}</span>
                                <span class="mt-0.5 block text-sm text-ink-secondary">Reported by {{ $report->reporter->name }} &middot; review of {{ $report->review->reviewee->name }} &middot; {{ $report->created_at->diffForHumans() }}</span>
                            </span>
                            <x-ui.icon name="chevron-right" class="size-4 text-ink-muted" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        @if($reports->hasPages())<div class="mt-5">{{ $reports->links() }}</div>@endif
    @endif
</x-layouts.admin>
