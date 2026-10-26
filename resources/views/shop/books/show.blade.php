@extends('layouts.shop')
@section('title', $book->title)

@section('content')
@php
    $first = $book->variants->first(function ($v) { return $v->stock > 0; }) ?: $book->variants->first();
    $initials = function ($name) { $p = explode(' ', trim($name)); return mb_strtoupper(mb_substr(end($p), 0, 1)); };
@endphp
<nav class="sb-breadcrumb">
    <a href="{{ route('home') }}"><i class="fas fa-home"></i> Trang chủ</a><i class="fas fa-chevron-right"></i>
    @if($book->category && $book->category->parent)<a href="{{ route('books.category', $book->category->parent->slug) }}">{{ $book->category->parent->name }}</a><i class="fas fa-chevron-right"></i>@endif
    @if($book->category)<a href="{{ route('books.category', $book->category->slug) }}">{{ $book->category->name }}</a><i class="fas fa-chevron-right"></i>@endif
    <span class="hien-tai">{{ $book->title }}</span>
</nav>

<section class="sb-card sb-detail">
    <div>
        <div class="sb-gallery-main">
            @if($first && $first->discount_percent > 0)<span class="sb-percent">-{{ $first->discount_percent }}%</span>@endif
            <img src="{{ $book->cover_url }}" alt="{{ $book->title }}">
        </div>
    </div>
    <div class="sb-detail-info">
        <div class="sb-detail-top">
            <span class="sb-eyebrow">{{ optional($book->category)->name }}</span>
            <span class="sb-pill {{ $book->total_stock > 0 ? 'ok' : 'bad' }}"><i class="fas {{ $book->total_stock > 0 ? 'fa-check-circle' : 'fa-times-circle' }}"></i> {{ $book->total_stock > 0 ? 'Còn hàng' : 'Hết hàng' }}</span>
        </div>
        <h1>{{ $book->title }}</h1>
        <div class="d-flex flex-wrap align-items-center gap-3 mb-2" style="font-size:.9rem">
            <span class="sb-stars">{!! stars($book->rating_avg) !!}<small>{{ number_format($book->rating_avg, 1) }} ({{ $book->rating_count }} đánh giá)</small></span>
            <span class="text-muted">Đã bán <b>{{ $book->sold_count }}</b></span>
            <span class="text-muted"><i class="far fa-eye"></i> {{ $book->view_count }}</span>
        </div>
        <div class="mb-3" style="font-size:.92rem">
            Tác giả:
            @foreach($book->authors as $a)<a href="{{ route('books.author', $a->slug) }}" class="fw-bold">{{ $a->name }}</a>@if(!$loop->last), @endif @endforeach
        </div>

        @if($first)
        <div class="sb-detail-price">
            <span class="sb-price" id="pPrice">{{ money($first->effective_price) }}</span>
            <span class="sb-old-price" id="pOld">{{ $first->compare_at_price ? money($first->compare_at_price) : '' }}</span>
            <span class="sb-sale-pill" id="pPct" @if(!$first->discount_percent) style="display:none" @endif>-{{ $first->discount_percent }}%</span>
        </div>
        @endif

        <div class="sb-mini-specs">
            <div><i class="fas fa-building"></i><span><b>Nhà xuất bản</b>{{ optional($book->publisher)->name ?: '—' }}</span></div>
            <div><i class="fas fa-calendar-alt"></i><span><b>Năm xuất bản</b>{{ $book->publish_year ?: '—' }}</span></div>
            <div><i class="fas fa-file-alt"></i><span><b>Số trang</b>{{ $book->pages ?: '—' }}</span></div>
            <div><i class="fas fa-barcode"></i><span><b>ISBN</b>{{ $book->isbn ?: '—' }}</span></div>
        </div>

        @if($first && $first->flash_item)
            <div class="sb-promo-box"><strong><i class="fas fa-bolt"></i> Đang Flash Sale</strong> – còn {{ $first->flash_item->remaining }} suất giá ưu đãi, kết thúc {{ optional(optional($first->flash_item->flashSale)->ends_at)->format('H:i d/m') }}.</div>
        @endif

        <form action="{{ route('cart.add') }}" method="post" id="buyForm">
            @csrf
            <div class="ms-variants">
                @foreach($book->variants as $v)
                    <div class="ms-variant">
                        <input type="radio" name="variant_id" value="{{ $v->id }}" id="v{{ $v->id }}" @if($first && $first->id == $v->id) checked @endif @if($v->stock <= 0) disabled @endif
                               data-price="{{ money($v->effective_price) }}" data-old="{{ $v->compare_at_price ? money($v->compare_at_price) : '' }}" data-stock="{{ $v->stock }}" data-pct="{{ $v->discount_percent }}">
                        <label for="v{{ $v->id }}"><b>{{ $v->name }} @if($v->flash_item)<i class="fas fa-bolt" style="color: var(--sb-accent)"></i>@endif</b><span>{{ money($v->effective_price) }}</span></label>
                    </div>
                @endforeach
            </div>
            <div class="sb-qty-box">
                <div><strong>Số lượng</strong><small id="pStock">{{ $first && $first->stock > 0 ? 'Còn ' . $first->stock . ' cuốn trong kho' : 'Tạm hết hàng' }}</small></div>
                <div class="sb-qty" data-qty>
                    <button type="button" data-step="-1" aria-label="Giảm">−</button>
                    <input type="number" name="quantity" id="q" value="1" min="1" max="{{ $first ? max(1, $first->stock) : 1 }}" aria-label="Số lượng">
                    <button type="button" data-step="1" aria-label="Tăng">+</button>
                </div>
            </div>
            <div class="sb-buy-row">
                <button class="sb-btn sb-btn-soft sb-btn-lg" @if($book->total_stock <= 0) disabled @endif><i class="fas fa-cart-plus"></i> Thêm vào giỏ</button>
                <button class="sb-btn sb-btn-lg" name="buy_now" value="1" @if($book->total_stock <= 0) disabled @endif><i class="fas fa-bolt"></i> Mua ngay</button>
            </div>
        </form>
        @auth
            <form action="{{ route('wishlist.toggle', $book) }}" method="post" class="mt-3">
                @csrf
                <button class="sb-btn sb-btn-outline sb-btn-sm"><i class="{{ $inWishlist ? 'fas' : 'far' }} fa-heart" style="color: var(--sb-red)"></i> {{ $inWishlist ? 'Đã yêu thích' : 'Thêm vào yêu thích' }}</button>
            </form>
        @endauth
        <ul class="sb-promise">
            <li><i class="fas fa-certificate"></i> Sách chính hãng 100% từ nhà xuất bản</li>
            <li><i class="fas fa-shipping-fast"></i> Miễn phí giao hàng cho đơn từ {{ money(\App\Services\CartService::FREE_SHIP_FROM) }}</li>
            <li><i class="fas fa-undo-alt"></i> Đổi trả trong 7 ngày nếu lỗi in, rách, giao nhầm</li>
        </ul>
    </div>
</section>

<section class="sb-card sb-tabs mt-4" id="reviews">
    <ul class="nav" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabMoTa" type="button">Giới thiệu</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabThongTin" type="button">Thông tin chi tiết</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDanhGia" type="button">Đánh giá ({{ $book->rating_count }})</button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="tabMoTa"><p style="white-space: pre-line">{{ $book->description ?: 'Đang cập nhật.' }}</p>
            @if($book->tags)<div class="sb-chip-list">@foreach(explode(',', $book->tags) as $t)<a class="sb-chip" href="{{ route('books.smart', ['q' => trim($t)]) }}">#{{ trim($t) }}</a>@endforeach</div>@endif
        </div>
        <div class="tab-pane fade" id="tabThongTin">
            <table class="sb-spec">
                <tr><th>Mã ISBN</th><td>{{ $book->isbn ?: '—' }}</td></tr>
                <tr><th>Tác giả</th><td>{{ $book->author_names }}</td></tr>
                <tr><th>Nhà xuất bản</th><td>{{ optional($book->publisher)->name ?: '—' }}</td></tr>
                <tr><th>Năm xuất bản</th><td>{{ $book->publish_year ?: '—' }}</td></tr>
                <tr><th>Số trang</th><td>{{ $book->pages ?: '—' }}</td></tr>
                <tr><th>Ngôn ngữ</th><td>{{ $book->language }}</td></tr>
                <tr><th>Thể loại</th><td>{{ optional($book->category)->name }}</td></tr>
                <tr><th>Phiên bản</th><td>@foreach($book->variants as $v){{ $v->name }} ({{ $v->sku }})@if(!$loop->last), @endif @endforeach</td></tr>
            </table>
        </div>
        <div class="tab-pane fade" id="tabDanhGia">
            <div class="sb-review-sum">
                <div class="sb-review-score"><b>{{ number_format($book->rating_avg, 1) }}</b><span class="sb-stars">{!! stars($book->rating_avg) !!}</span><div class="small text-muted">{{ $book->rating_count }} đánh giá</div></div>
                <div class="sb-review-bars">
                    @foreach($ratingStats as $star => $count)
                        <div><span>{{ $star }} <i class="fas fa-star" style="color: var(--sb-yellow)"></i></span><span class="bar"><i style="width: {{ $book->rating_count ? $count * 100 / $book->rating_count : 0 }}%"></i></span><span>{{ $count }}</span></div>
                    @endforeach
                </div>
            </div>
            @auth
                @if($canReview)
                    <form action="{{ route('books.review', $book) }}" method="post" class="sb-note-box my-3">
                        @csrf
                        <b>{{ $myReview ? 'Sửa đánh giá của bạn' : 'Viết đánh giá của bạn' }}</b>
                        <div class="sb-star-input my-2">
                            @for($i = 5; $i >= 1; $i--)
                                <input type="radio" name="rating" value="{{ $i }}" id="r{{ $i }}" @if(optional($myReview)->rating == $i || (!$myReview && $i == 5)) checked @endif><label for="r{{ $i }}" title="{{ $i }} sao"><i class="fas fa-star"></i></label>
                            @endfor
                        </div>
                        <textarea name="comment" class="sb-control mb-2" placeholder="Cảm nhận về nội dung, chất lượng in, giao hàng...">{{ optional($myReview)->comment }}</textarea>
                        <button class="sb-btn sb-btn-sm"><i class="fas fa-paper-plane"></i> Gửi đánh giá</button>
                    </form>
                @else
                    <div class="sb-note-box my-3">Chỉ khách hàng đã mua và nhận sách này mới có thể đánh giá.</div>
                @endif
            @else
                <div class="sb-note-box my-3"><a href="{{ route('login') }}">Đăng nhập</a> để đánh giá sách bạn đã mua.</div>
            @endauth
            @forelse($book->reviews as $r)
                <div class="sb-review">
                    <div class="sb-review-head">
                        <span class="sb-av">{{ $initials(optional($r->user)->name ?: 'K') }}</span>
                        <div><b>{{ optional($r->user)->name ?: 'Khách hàng' }}</b> <span class="sb-pill ok"><i class="fas fa-check"></i> Đã mua</span><br><span class="sb-stars">{!! stars($r->rating) !!}<small>{{ $r->created_at->format('d/m/Y') }}</small></span></div>
                    </div>
                    @if($r->comment)<p>{{ $r->comment }}</p>@endif
                </div>
            @empty
                <p class="text-muted">Chưa có đánh giá nào.</p>
            @endforelse
        </div>
    </div>
</section>

@if($similar->count())
<section class="sb-section reveal" style="margin-top:60px">
    <div class="sb-section-head"><div><span class="sb-eyebrow">Gợi ý theo nội dung, tác giả & thể loại</span><h2>Sách tương tự</h2></div></div>
    <div class="sb-strip-wrap"><div class="sb-strip">@foreach($similar as $book_)@include('partials.book-card', ['book' => $book_])@endforeach</div></div>
</section>
@endif
@if($alsoBought->count())
<section class="sb-section reveal" style="margin-top:50px">
    <div class="sb-section-head"><div><span class="sb-eyebrow">Collaborative filtering</span><h2>Khách mua sách này cũng mua</h2></div></div>
    <div class="sb-strip-wrap"><div class="sb-strip">@foreach($alsoBought as $book_)@include('partials.book-card', ['book' => $book_])@endforeach</div></div>
</section>
@endif
@endsection

@push('scripts')
<script>
    document.querySelectorAll('input[name="variant_id"]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.getElementById('pPrice').textContent = r.dataset.price;
            document.getElementById('pOld').textContent = r.dataset.old;
            var pct = document.getElementById('pPct');
            pct.textContent = '-' + r.dataset.pct + '%';
            pct.style.display = r.dataset.pct > 0 ? '' : 'none';
            document.getElementById('pStock').textContent = r.dataset.stock > 0 ? 'Còn ' + r.dataset.stock + ' cuốn trong kho' : 'Tạm hết hàng';
            var q = document.getElementById('q'); q.max = Math.max(1, r.dataset.stock); if (+q.value > +q.max) q.value = q.max;
        });
    });
</script>
@endpush
