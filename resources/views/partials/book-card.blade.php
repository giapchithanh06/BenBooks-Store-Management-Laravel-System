@php
    $v = $book->display_variant;
    $stock = $book->variants->sum('stock');
@endphp
<div class="sb-product">
    <a class="sb-product-img" href="{{ route('books.show', $book->slug) }}">
        @if($v && $v->flash_item)<span class="sb-tag giam"><i class="fas fa-bolt"></i> Flash</span>@elseif($book->is_featured)<span class="sb-tag sale">Nổi bật</span>@elseif($book->publish_year >= date('Y') - 3)<span class="sb-tag moi">Mới</span>@endif
        @if($stock <= 0)<span class="sb-tag het">Hết hàng</span>@endif
        @if($v && $v->discount_percent > 0)<span class="sb-percent">-{{ $v->discount_percent }}%</span>@endif
        <img src="{{ $book->cover_url }}" alt="{{ $book->title }}" loading="lazy">
    </a>
    <div class="sb-product-body">
        <span class="sb-product-hang">{{ $book->author_names }}</span>
        <h3 class="sb-product-name"><a href="{{ route('books.show', $book->slug) }}" title="{{ $book->title }}">{{ $book->title }}</a></h3>
        @if($book->rating_count)
            <span class="sb-stars">{!! stars($book->rating_avg) !!}<small>({{ $book->rating_count }})</small></span>
        @else
            <span class="sb-stars trong"><i class="far fa-star"></i> Chưa có đánh giá</span>
        @endif
        <div class="sb-product-price">
            @if($v)
                <span class="sb-price">{{ money($v->effective_price) }}</span>
                @if($v->compare_at_price)<span class="sb-old-price">{{ money($v->compare_at_price) }}</span>@endif
            @endif
        </div>
        <span class="sb-sold">Đã bán {{ $book->sold_count }}</span>
        <div class="sb-product-actions">
            @if($v && $stock > 0)
                <form action="{{ route('cart.add') }}" method="post" data-ajax="cart" data-cart-url="{{ route('cart.index') }}">
                    @csrf
                    <input type="hidden" name="variant_id" value="{{ $v->id }}">
                    <button class="sb-btn sb-btn-sm"><i class="fas fa-cart-plus"></i> Thêm giỏ</button>
                </form>
            @endif
            <a class="sb-btn sb-btn-soft sb-btn-sm" href="{{ route('books.show', $book->slug) }}">Chi tiết</a>
        </div>
    </div>
</div>
