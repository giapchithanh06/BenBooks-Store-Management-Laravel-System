@if ($paginator->hasPages())
    <ul class="sb-pagination">
        <li class="{{ $paginator->onFirstPage() ? 'disabled' : '' }}">
            @if($paginator->onFirstPage())<span><i class="fas fa-chevron-left"></i></span>@else<a href="{{ $paginator->previousPageUrl() }}" aria-label="Trang trước"><i class="fas fa-chevron-left"></i></a>@endif
        </li>
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="disabled"><span>{{ $element }}</span></li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="active"><span>{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach
        <li class="{{ $paginator->hasMorePages() ? '' : 'disabled' }}">
            @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" aria-label="Trang sau"><i class="fas fa-chevron-right"></i></a>@else<span><i class="fas fa-chevron-right"></i></span>@endif
        </li>
    </ul>
@endif
