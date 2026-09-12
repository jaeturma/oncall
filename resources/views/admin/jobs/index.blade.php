<x-layouts.admin title="Jobs" description="Every confirmed booking on the platform.">
    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Job</th><th>Customer</th><th>Provider</th><th class="num">Agreed price</th><th>Status</th><th>Booked</th></tr></thead>
                <tbody>
                    @forelse($jobs as $job)
                        <tr>
                            <td><p class="font-semibold text-ink">#{{ $job->id }} &middot; {{ $job->serviceRequest->service->name }}</p><p class="truncate text-xs text-ink-muted">{{ $job->serviceRequest->title }}</p></td>
                            <td class="whitespace-nowrap">{{ $job->serviceFinder->name }}</td>
                            <td class="whitespace-nowrap">{{ $job->provider->name }}</td>
                            <td class="num font-semibold whitespace-nowrap">₱{{ number_format((float) $job->agreed_price, 2) }}</td>
                            <td><x-ui.status-badge :status="$job->status" /></td>
                            <td class="whitespace-nowrap text-ink-secondary">{{ $job->created_at->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-ink-secondary">No jobs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @if($jobs->hasPages())<div class="mt-5">{{ $jobs->links() }}</div>@endif
</x-layouts.admin>
