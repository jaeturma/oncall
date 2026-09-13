<x-layouts.admin title="SMS delivery logs" description="Delivery history only — never the code, credentials, or full provider response.">
    <div class="mb-5 flex flex-wrap gap-2">
        <x-ui.button :href="route('admin.sms-logs.index')" :variant="!$status ? 'dark' : 'secondary'" size="sm">All</x-ui.button>
        @foreach(App\Enums\SmsDeliveryStatus::cases() as $case)
            <x-ui.button :href="route('admin.sms-logs.index', ['status' => $case->value])" :variant="$status === $case ? 'dark' : 'secondary'" size="sm">{{ str($case->value)->lower()->ucfirst() }}</x-ui.button>
        @endforeach
    </div>

    @if($logs->isEmpty())
        <x-empty-state icon="chat-bubble-left-right" title="No SMS activity" message="No messages match this filter." />
    @else
        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>Mobile</th><th>User</th><th>Type</th><th>Provider</th><th>Status</th><th>Sent</th></tr></thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td class="font-mono">{{ $log->maskedMobile() }}</td>
                            <td>{{ $log->user?->name ?? '—' }}</td>
                            <td>{{ str($log->message_type)->replace('_', ' ')->lower()->ucfirst() }}</td>
                            <td>{{ $log->provider }}</td>
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
