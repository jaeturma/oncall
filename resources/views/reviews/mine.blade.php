<x-layouts.app title="My reviews" description="Reviews you've written for providers you've booked.">
    @if($reviews->isEmpty())
        <x-empty-state icon="star" title="No reviews yet" message="Once a booking is completed, you can leave a review from the job page." />
    @else
        <div class="card">
            <ul class="stack-list">
                @foreach($reviews as $review)
                    <li class="px-5 py-4 sm:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-ink">{{ $review->reviewee->name }}</p>
                            <span class="text-gold-500" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                        </div>
                        @if($review->job?->serviceRequest?->service)<p class="mt-0.5 text-xs text-ink-muted">{{ $review->job->serviceRequest->service->name }} &middot; {{ $review->created_at->format('M j, Y') }}</p>@endif
                        @if($review->comment)<p class="mt-2 whitespace-pre-line text-sm text-ink-secondary">{{ $review->comment }}</p>@endif
                        <p class="mt-1 text-xs text-ink-muted">Status: {{ str($review->status->value)->title() }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
        @if($reviews->hasPages())<div class="mt-6">{{ $reviews->links() }}</div>@endif
    @endif
</x-layouts.app>
