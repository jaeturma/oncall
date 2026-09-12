<x-layouts.admin title="Enforcement cases" description="Cases opened from reports, disputes, or system flags. Apply the next reviewed step or resolve the case.">
    @if($cases->isEmpty())
        <x-empty-state icon="scale" title="No enforcement cases" message="Cases opened from reports or disputes will appear here." />
    @else
        <section class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Account</th><th>Violation</th><th>Severity</th><th>Source</th><th>Status</th><th>Opened</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach($cases as $case)
                            <tr>
                                <td class="font-semibold whitespace-nowrap">{{ $case->user->name }}</td>
                                <td class="whitespace-nowrap">{{ str($case->violation_category->value)->replace('_', ' ')->lower()->ucfirst() }}</td>
                                <td><x-ui.badge :tone="in_array($case->severity->value, ['HIGH', 'CRITICAL']) ? 'danger' : 'neutral'">{{ str($case->severity->value)->lower()->ucfirst() }}</x-ui.badge></td>
                                <td class="whitespace-nowrap text-ink-secondary">{{ $case->relatedReport?->reporter?->name ?? 'System flag' }}</td>
                                <td><x-ui.status-badge :status="$case->status" /></td>
                                <td class="whitespace-nowrap text-ink-secondary">{{ $case->created_at->format('M j, Y') }}</td>
                                <td class="text-right whitespace-nowrap"><a class="text-sm font-semibold text-navy-800 hover:underline" href="{{ route('admin.enforcement.show', $case) }}">Review</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @if($cases->hasPages())<div class="mt-5">{{ $cases->links() }}</div>@endif
    @endif
</x-layouts.admin>
