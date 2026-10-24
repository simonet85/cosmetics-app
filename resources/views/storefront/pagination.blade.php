@if ($paginator->hasPages())
    @php
        $link = 'inline-flex min-h-12 min-w-12 items-center justify-center border border-ink px-4 font-semibold hover:bg-ink hover:text-white';
        $off = 'inline-flex min-h-12 min-w-12 items-center justify-center border border-line px-4 text-ink-muted';
    @endphp
    <nav aria-label="Pagination" class="mt-8 flex items-center justify-between gap-3">
        @if ($paginator->onFirstPage())
            <span class="{{ $off }}" aria-disabled="true">Précédent</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $link }}">Précédent</a>
        @endif

        <p class="tnum text-center text-sm text-ink-muted">Page {{ $paginator->currentPage() }} sur {{ $paginator->lastPage() }}</p>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $link }}">Suivant</a>
        @else
            <span class="{{ $off }}" aria-disabled="true">Suivant</span>
        @endif
    </nav>
@endif
