@php
    $me = auth()->user();
@endphp

<x-layouts.app title="Messages" description="Every booking's message thread in one place. Send a reply from the booking page.">
    @if($conversations->isEmpty())
        <x-empty-state icon="chat" title="No conversations yet" message="Once a booking is confirmed, messages you send or receive on that job will appear here." />
    @else
        <div class="card">
            <ul class="stack-list">
                @foreach($conversations as $job)
                    @php
                        $other = $job->otherParticipant($me);
                        $last = $job->latestMessage;
                        $unread = $job->unread_count > 0;
                    @endphp
                    <li>
                        <a class="flex items-start gap-3 px-5 py-4 hover:bg-surface-muted sm:px-6 {{ $unread ? 'bg-gold-50/40' : '' }}" href="{{ route('jobs.show', $job) }}">
                            <x-ui.avatar :name="$other?->name" size="md" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
                                    <p class="truncate font-semibold text-ink">{{ $other?->name ?? 'Deleted user' }}</p>
                                    @if($last)<time class="shrink-0 text-xs text-ink-muted" datetime="{{ $last->created_at->toIso8601String() }}">{{ $last->created_at->diffForHumans() }}</time>@endif
                                </div>
                                <p class="truncate text-xs font-medium text-navy-700">{{ $job->serviceRequest->service->name }} &middot; {{ $job->serviceRequest->title }}</p>
                                @if($last)
                                    <p class="mt-1 truncate text-sm {{ $unread ? 'font-semibold text-ink' : 'text-ink-secondary' }}">{{ $last->sender_id === $me->id ? 'You: ' : '' }}{{ str($last->body)->limit(90) }}</p>
                                @endif
                            </div>
                            @if($unread)<span class="mt-1 flex size-2.5 shrink-0 rounded-full bg-gold-500" aria-label="Unread messages"></span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        @if($conversations->hasPages())<div class="mt-6">{{ $conversations->links() }}</div>@endif
    @endif
</x-layouts.app>
