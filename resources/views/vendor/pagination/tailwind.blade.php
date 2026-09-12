@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-muted">
            Showing <span class="font-semibold text-ink">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-ink">{{ $paginator->lastItem() }}</span> of <span class="font-semibold text-ink">{{ $paginator->total() }}</span>
        </p>
        <ul class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <li><span class="btn btn-secondary btn-sm cursor-not-allowed opacity-50" aria-disabled="true"><x-ui.icon name="chevron-right" class="size-4 rotate-180" /><span class="sr-only sm:not-sr-only">Previous</span></span></li>
            @else
                <li><a class="btn btn-secondary btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev"><x-ui.icon name="chevron-right" class="size-4 rotate-180" /><span class="sr-only sm:not-sr-only">Previous</span></a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="hidden px-2 text-sm text-ink-muted sm:block" aria-hidden="true">{{ $element }}</li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="hidden sm:block"><span class="btn btn-dark btn-sm min-w-9 px-2" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li class="hidden sm:block"><a class="btn btn-ghost btn-sm min-w-9 px-2" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            <li class="px-2 text-sm text-ink-muted sm:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</li>

            @if ($paginator->hasMorePages())
                <li><a class="btn btn-secondary btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next"><span class="sr-only sm:not-sr-only">Next</span><x-ui.icon name="chevron-right" class="size-4" /></a></li>
            @else
                <li><span class="btn btn-secondary btn-sm cursor-not-allowed opacity-50" aria-disabled="true"><span class="sr-only sm:not-sr-only">Next</span><x-ui.icon name="chevron-right" class="size-4" /></span></li>
            @endif
        </ul>
    </nav>
@endif
