<x-layouts.admin title="Notification templates" description="Per-event push and in-app text. A missing or disabled override always falls back to the safe application default.">
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Event</th><th>Category</th><th>Mandatory</th><th>Override</th><th></th></tr></thead>
            <tbody>
                @foreach($events as $event)
                    <tr>
                        <td class="font-mono text-xs">{{ $event['key'] }}</td>
                        <td>{{ str($event['category'])->replace('_', ' ')->title() }}</td>
                        <td>{{ $event['mandatory'] ? 'Yes' : 'No' }}</td>
                        <td>{{ $event['has_override'] ? ($event['enabled'] ? 'Custom (enabled)' : 'Custom (disabled)') : 'Default' }}</td>
                        <td><a class="link" href="{{ route('admin.settings.notification-templates.edit', $event['key']) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
