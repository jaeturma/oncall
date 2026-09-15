<x-layouts.app title="Reviews received" description="What customers have said after a completed booking.">
    @if($reviews->isEmpty())
        <x-empty-state icon="star" title="No reviews yet" message="Reviews appear here once a customer reviews a completed booking with you." />
    @else
        <div class="card">
            <ul class="stack-list">
                @foreach($reviews as $review)
                    <li class="px-5 py-4 sm:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-ink">{{ $review->reviewer->name }}</p>
                            <span class="text-gold-500" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                        </div>
                        @if($review->job?->serviceRequest?->service)<p class="mt-0.5 text-xs text-ink-muted">{{ $review->job->serviceRequest->service->name }} &middot; {{ $review->created_at->format('M j, Y') }}</p>@endif
                        @if($review->comment)<p class="mt-2 whitespace-pre-line text-sm text-ink-secondary">{{ $review->comment }}</p>@endif

                        @if($review->hasResponse())
                            <div class="mt-2 rounded-lg bg-surface-muted p-3">
                                <p class="text-xs font-semibold text-ink">Your response</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-ink-secondary">{{ $review->response }}</p>
                            </div>
                        @elseif($review->status->value === 'PUBLISHED')
                            <form class="mt-2 grid gap-2" method="POST" action="{{ route('reviews.response.store', $review) }}">
                                @csrf
                                <textarea class="textarea text-sm" name="response" rows="2" maxlength="1000" placeholder="Write a public response…" required></textarea>
                                <div><x-ui.button variant="secondary" size="sm">Post response</x-ui.button></div>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        @if($reviews->hasPages())<div class="mt-6">{{ $reviews->links() }}</div>@endif
    @endif
</x-layouts.app>
