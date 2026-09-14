<x-layouts.admin title="Notification delivery logs" description="Delivery history only — never Firebase credentials, full device tokens, or payload content.">
    <div class="mb-5 flex flex-wrap gap-2">
        <x-ui.button :href="route('admin.notifications.logs.index')" :variant="!$channel && !$status ? 'dark' : 'secondary'" size="sm">All</x-ui.button>
        @foreach(App\Enums\NotificationChannel::cases() as $case)
            <x-ui.button :href="route('admin.notifications.logs.index', ['channel' => $case->value])" :variant="$channel === $case ? 'dark' : 'secondary'" size="sm">{{ str($case->value)->lower()->ucfirst() }}</x-ui.button>
        @endforeach
    </div>

    @if($logs->isEmpty())
        <x-empty-state icon="bell" title="No notification activity" message="No deliveries match this filter." />
    @else
        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>Event</th><th>User</th><th>Channel</th><th>Platform</th><th>Status</th><th>When</th></tr></thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td class="font-mono text-xs">{{ $log->event_key }}</td>
                            <td>{{ $log->user?->name ?? '—' }}</td>
                            <td>{{ str($log->channel->value)->lower()->ucfirst() }}</td>
                            <td>{{ $log->device?->platform?->value ?? '—' }}</td>
                            <td><x-ui.status-badge :status="$log->status" /></td>
                            <td class="whitespace-nowrap text-ink-secondary">{{ $log->sent_at?->format('M j, g:i A') ?? $log->created_at->format('M j, g:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())<div class="mt-5">{{ $logs->links() }}</div>@endif
    @endif
</x-layouts.admin>
