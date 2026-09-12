<x-layouts.admin title="Safety reports" description="Reports submitted from bookings. Each open report links to its enforcement review.">
    @if($reports->isEmpty())
        <x-empty-state icon="flag" title="No safety reports" message="Reports submitted by users will appear here for review." />
    @else
        <div class="grid gap-4">
            @foreach($reports as $report)
                <article class="card card-pad">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-ink">{{ $report->reporter->name }} <span class="font-normal text-ink-muted">reported</span> {{ $report->reportedUser->name }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm"><span class="badge badge-danger">{{ str($report->category->value)->replace('_', ' ')->lower()->ucfirst() }}</span><span class="text-ink-muted">{{ $report->created_at->diffForHumans() }}</span></p>
                        </div>
                        <x-ui.status-badge :status="$report->status" />
                    </div>
                    <p class="mt-3 whitespace-pre-line rounded-lg bg-surface-muted p-3 text-sm text-ink">{{ $report->description }}</p>
                    @if($report->enforcementCase)
                        <a class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-navy-800 hover:underline" href="{{ route('admin.enforcement.show', $report->enforcementCase) }}">Open enforcement review <x-ui.icon name="arrow-right" class="size-4" /></a>
                    @endif
                </article>
            @endforeach
        </div>
        @if($reports->hasPages())<div class="mt-5">{{ $reports->links() }}</div>@endif
    @endif
</x-layouts.admin>
