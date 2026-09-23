@if ($paginator->hasPages())
    <nav class="panel-pager" aria-label="Phân trang">
        @if ($paginator->onFirstPage())
            <span class="btn btn-ghost btn-sm" aria-disabled="true">← Trước</span>
        @else
            <a class="btn btn-ghost btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">← Trước</a>
        @endif

        <span class="muted">Trang {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn btn-ghost btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Sau →</a>
        @else
            <span class="btn btn-ghost btn-sm" aria-disabled="true">Sau →</span>
        @endif
    </nav>
@endif
