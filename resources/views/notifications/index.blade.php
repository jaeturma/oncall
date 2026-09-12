<x-layouts.app title="Notifications" :description="$unreadCount > 0 ? $unreadCount.' unread' : 'You are all caught up.'">
    @if($unreadCount > 0)
        <x-slot:actions>
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<x-ui.button variant="secondary" size="sm" icon="check" data-loading-text="Marking…">Mark all as read</x-ui.button></form>
        </x-slot:actions>
    @endif

    @if($notifications->isEmpty())
        <x-empty-state icon="bell" title="No notifications yet" message="Updates about your requests, bookings, wallet, and account will land here." />
    @else
        <div class="card">
            <ul class="stack-list">
                @foreach($notifications as $notification)
                    @php($unread = $notification->read_at === null)
                    <li class="flex items-start gap-3 px-5 py-4 sm:px-6 {{ $unread ? 'bg-gold-50/40' : '' }}">
                        <span class="mt-2 size-2 shrink-0 rounded-full {{ $unread ? 'bg-gold-500' : 'bg-transparent' }}" aria-hidden="true"></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ink">{{ $notification->data['title'] ?? 'Update' }}@if($unread)<span class="sr-only"> (unread)</span>@endif</p>
                            <p class="mt-0.5 text-sm text-ink-secondary">{{ $notification->data['body'] ?? '' }}</p>
                            <time class="mt-1 block text-xs text-ink-muted" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                        </div>
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" data-skip-loading>
                            @csrf @method('PATCH')
                            @if($notification->data['url'] ?? null)
                                <x-ui.button variant="secondary" size="sm" icon-right="arrow-right">Open</x-ui.button>
                            @elseif($unread)
                                <x-ui.button variant="ghost" size="sm">Mark read</x-ui.button>
                            @endif
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
        @if($notifications->hasPages())<div class="mt-6">{{ $notifications->links() }}</div>@endif
    @endif
</x-layouts.app>
