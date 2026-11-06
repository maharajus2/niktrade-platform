@if ($paginator->hasPages())
    <nav class="catalog-pagination" aria-label="Страницы каталога">
        <span class="catalog-pagination-summary">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} из {{ $paginator->total() }} товаров
        </span>
        <div class="catalog-pagination-links">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Предыдущая страница">←</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Предыдущая страница">←</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Страница {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Следующая страница">→</a>
            @else
                <span aria-disabled="true" aria-label="Следующая страница">→</span>
            @endif
        </div>
    </nav>
@endif
