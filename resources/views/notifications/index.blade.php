<x-layouts.app title="Notifications">
    <div class="grid gap-6">
        <x-flash />

        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><h1 class="text-3xl font-black">Notifications</h1><p class="mt-2 text-slate-600">{{ $unreadCount }} unread</p></div>
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold">Mark all read</button></form>
            @endif
        </div>

        <div class="grid gap-3">
            @forelse($notifications as $notification)
                <article class="flex items-start gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 {{ $notification->read_at ? 'ring-slate-200' : 'ring-gold-300' }}">
                    <span class="mt-1 size-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-300' : 'bg-gold-400' }}" aria-hidden="true"></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-black">{{ $notification->data['title'] ?? 'Update' }}</p>
                        <p class="mt-1 text-slate-700">{{ $notification->data['body'] ?? '' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf @method('PATCH')
                        <button class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-bold">{{ ($notification->data['url'] ?? null) ? 'Open' : 'Mark read' }}</button>
                    </form>
                </article>
            @empty
                <x-empty-state title="You have no notifications yet" message="Updates about your requests, bookings, wallet, and account land here." />
            @endforelse
        </div>
        <div>{{ $notifications->links() }}</div>
    </div>
</x-layouts.app>
