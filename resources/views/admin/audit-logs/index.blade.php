<x-layouts.admin title="Audit log" description="Immutable record of sensitive actions across the platform.">
    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>When</th><th>Event</th><th>Actor</th><th>Subject</th></tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap text-ink-secondary"><time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('M j, Y g:i A') }}</time></td>
                            <td><code class="rounded bg-surface-muted px-1.5 py-0.5 text-xs font-semibold text-navy-900">{{ $log->event }}</code></td>
                            <td class="whitespace-nowrap">{{ $log->actor?->name ?? 'System' }}</td>
                            <td class="whitespace-nowrap text-ink-secondary">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-10 text-center text-ink-secondary">No audit events recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @if($logs->hasPages())<div class="mt-5">{{ $logs->links() }}</div>@endif
</x-layouts.admin>
