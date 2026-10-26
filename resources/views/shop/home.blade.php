@extends('layouts.shop')
@section('title', 'Trang chủ')

@section('content')
{{-- 1. HERO: chữ bên trái, sách nổi bật xoay vòng bên phải --}}
<section class="sb-hero2">
    <div>
        <span class="sb-eyebrow" style="color: #FFB27D;"><i class="fas fa-book-open"></i> Sách chính hãng · Giá sinh viên</span>
        <h1>Mỗi trang sách,<br><span>một chân trời mới</span></h1>
        <p>BenBooks tuyển chọn văn học, kinh tế, kỹ năng, thiếu nhi và hàng trăm tập manga, manhwa, comic. Giao nhanh toàn quốc, đổi trả 7 ngày, freeship đơn từ 300.000₫.</p>
        <div class="sb-hero-actions">
            <a class="sb-btn sb-btn-grad sb-btn-lg" href="{{ route('books.index') }}">Khám phá sách <i class="fas fa-arrow-right"></i></a>
            <a class="sb-btn sb-btn-outline sb-btn-lg" href="{{ route('books.smart') }}"><i class="fas fa-magic"></i> Hỏi AI tìm sách</a>
        </div>
        <div class="sb-hero-chips">
            <span><i class="fas fa-shield-alt"></i> Sách chính hãng</span>
            <span><i class="fas fa-shipping-fast"></i> Freeship từ 300k</span>
            <span><i class="fas fa-undo-alt"></i> Đổi trả 7 ngày</span>
        </div>
    </div>
    <div class="sb-stage" aria-label="Sách nổi bật">
        <div class="sb-slider" data-slider>
            @foreach($featured->take(6) as $i => $b)
                @php $fv = $b->display_variant; @endphp
                <div class="sb-slide {{ $i === 0 ? 'active' : '' }}" data-url="{{ route('books.show', $b->slug) }}" data-title="{{ $b->title }}"
                     data-price="{{ $fv ? money($fv->effective_price) : '' }}" data-pct="{{ $fv ? $fv->discount_percent : 0 }}">
                    <a href="{{ route('books.show', $b->slug) }}"><img src="{{ $b->cover_url }}" alt="{{ $b->title }}"></a>
                </div>
            @endforeach
            <div class="sb-dots"></div>
        </div>
        <div class="sb-stage-badge" hidden></div>
        <a class="sb-stage-card" href="{{ route('books.index') }}"><small>Đang được quan tâm</small><b></b><span class="sb-price"></span></a>
    </div>
</section>

{{-- 2. CAM KẾT --}}
<section class="sb-features">
    <div class="sb-feature"><i class="fas fa-certificate"></i><div><strong>Sách chính hãng</strong><span>Từ NXB & nhà phát hành uy tín</span></div></div>
    <div class="sb-feature"><i class="fas fa-shipping-fast"></i><div><strong>Giao nhanh</strong><span>Miễn phí từ {{ money(\App\Services\CartService::FREE_SHIP_FROM) }}</span></div></div>
    <div class="sb-feature"><i class="fas fa-undo-alt"></i><div><strong>Đổi trả 7 ngày</strong><span>Sách lỗi in, rách là đổi mới</span></div></div>
    <div class="sb-feature"><i class="fas fa-headset"></i><div><strong>Hỗ trợ 8h – 21h</strong><span>Hotline 1900 6868</span></div></div>
</section>

{{-- 3. MUA THEO NHU CẦU --}}
<section class="sb-section reveal">
    <div class="sb-section-head"><div><span class="sb-eyebrow">Đọc theo nhu cầu</span><h2>Hôm nay bạn muốn đọc gì?</h2></div></div>
    <div class="sb-needs">
        <a class="sb-need" href="{{ route('books.index', ['price_max' => 100000, 'sort' => 'best']) }}"><i class="fas fa-graduation-cap"></i><strong>Túi tiền sinh viên</strong><span>Sách hay giá dưới 100.000₫.</span><em>{{ $needs['cheap'] }} đầu sách</em></a>
        <a class="sb-need" href="{{ route('books.category', 'truyen-tranh') }}"><i class="fas fa-dragon"></i><strong>Truyện tranh</strong><span>Manga, manhwa, manhua, comic, truyện Việt.</span><em>{{ $needs['comic'] }} đầu truyện</em></a>
        <a class="sb-need" href="{{ route('books.category', 'ky-nang-song') }}"><i class="fas fa-lightbulb"></i><strong>Phát triển bản thân</strong><span>Kỹ năng sống, tư duy, tài chính.</span><em>{{ $needs['skill'] }} đầu sách</em></a>
        <a class="sb-need" href="{{ route('books.category', 'thieu-nhi') }}"><i class="fas fa-child"></i><strong>Cho bé & gia đình</strong><span>Sách thiếu nhi, truyện tuổi thơ.</span><em>{{ $needs['kids'] }} đầu sách</em></a>
    </div>
</section>

{{-- 4. FLASH SALE --}}
@if($flashSale && $flashItems->count())
<section class="sb-section reveal">
    <div class="ms-flash">
        <div class="ms-flash-head">
            <h2><i class="fas fa-bolt"></i> Flash Sale</h2>
            <span class="text-white-50">{{ $flashSale->name }} · kết thúc sau</span>
            <span class="ms-countdown" data-countdown="{{ $flashSale->ends_at->toIso8601String() }}"></span>
            <a class="sb-btn sb-btn-white ms-auto" href="{{ route('flash-sale') }}">Xem tất cả <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="sb-products cot-5">
            @foreach($flashItems->take(5) as $item)
                @php $fb = $item->variant->book; @endphp
                <div class="sb-product">
                    <a class="sb-product-img" href="{{ route('books.show', $fb->slug) }}">
                        <span class="sb-tag giam"><i class="fas fa-bolt"></i> Flash</span>
                        <span class="sb-percent">-{{ (int) round(100 - $item->sale_price * 100 / max(1, $item->variant->price)) }}%</span>
                        <img src="{{ $fb->cover_url }}" alt="{{ $fb->title }}" loading="lazy">
                    </a>
                    <div class="sb-product-body">
                        <span class="sb-product-hang">{{ $item->variant->name }}</span>
                        <h3 class="sb-product-name"><a href="{{ route('books.show', $fb->slug) }}">{{ $fb->title }}</a></h3>
                        <div class="sb-product-price"><span class="sb-price">{{ money($item->sale_price) }}</span><span class="sb-old-price">{{ money($item->variant->price) }}</span></div>
                        <div class="ms-progress"><i style="width: {{ max(6, $item->sold_percent) }}%"></i><small>{{ $item->remaining > 0 ? 'Đã bán ' . $item->sold . '/' . $item->quantity : 'Hết suất' }}</small></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 5. GỢI Ý CÁ NHÂN HÓA --}}
<section class="sb-section reveal">
    <div class="sb-section-head">
        <div><span class="sb-eyebrow"><i class="fas fa-magic"></i> Dành riêng cho bạn</span><h2>Gợi ý cho bạn</h2></div>
        <p class="text-muted mb-0 small">{{ $interests ? 'Dựa trên sở thích: ' . implode(', ', $interests) : 'Những cuốn được độc giả yêu thích nhất' }}</p>
    </div>
    <div class="sb-products cot-5">
        @foreach($recommended->take(10) as $book)@include('partials.book-card')@endforeach
    </div>
</section>

{{-- 6. NỔI BẬT --}}
<section class="sb-section reveal">
    <div class="sb-section-head">
        <div><span class="sb-eyebrow">Được yêu thích</span><h2>Sách nổi bật</h2></div>
        <a class="sb-btn sb-btn-grad" href="{{ route('books.index', ['sort' => 'rating']) }}">Xem thêm</a>
    </div>
    <div class="sb-products">
        @foreach($featured->take(8) as $book)@include('partials.book-card')@endforeach
    </div>
</section>

{{-- 7. KHUYẾN MÃI --}}
@if($promoVoucher)
<section class="sb-section reveal">
    <div class="sb-promo">
        <div>
            <span class="sb-eyebrow"><i class="fas fa-ticket-alt"></i> Ưu đãi cho BenBooks</span>
            <h2>{{ $promoVoucher->name }}</h2>
            <p>{{ $promoVoucher->description }}{{ $promoVoucher->ends_at ? ' · HSD ' . $promoVoucher->ends_at->format('d/m/Y') : '' }}. Nhập mã ở bước giỏ hàng.</p>
            <span class="sb-voucher-chip"><i class="fas fa-ticket-alt"></i> {{ $promoVoucher->code }}</span>
        </div>
        <a class="sb-btn sb-btn-white sb-btn-lg" href="{{ route('vouchers') }}">Săn thêm mã <i class="fas fa-arrow-right"></i></a>
    </div>
</section>
@endif

{{-- 8. DẢI NGANG CUỘN --}}
@foreach([['Truyện tranh hot', 'Manga · Manhwa · Comic', $comics, route('books.category', 'truyen-tranh'), 'stripComic'],
          ['Bán chạy nhất', 'Top doanh số', $bestSellers, route('books.index', ['sort' => 'best']), 'stripBest'],
          ['Mới xuất bản', 'Vừa lên kệ', $newest, route('books.index', ['sort' => 'year']), 'stripNew']] as $strip)
    @if(count($strip[2]))
    <section class="sb-section reveal">
        <div class="sb-section-head">
            <div><span class="sb-eyebrow">{{ $strip[1] }}</span><h2>{{ $strip[0] }}</h2></div>
            <div class="sb-strip-btns">
                <button type="button" aria-label="Trước" onclick="document.getElementById('{{ $strip[4] }}').scrollBy({left: -540, behavior: 'smooth'})"><i class="fas fa-chevron-left"></i></button>
                <a class="sb-btn sb-btn-soft" href="{{ $strip[3] }}">Xem tất cả</a>
                <button type="button" aria-label="Sau" onclick="document.getElementById('{{ $strip[4] }}').scrollBy({left: 540, behavior: 'smooth'})"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
        <div class="sb-strip-wrap"><div class="sb-strip" id="{{ $strip[4] }}">
            @foreach($strip[2] as $book)@include('partials.book-card')@endforeach
        </div></div>
    </section>
    @endif
@endforeach

{{-- 9. THỂ LOẠI --}}
<section class="sb-section reveal">
    <div class="sb-section-head"><div><span class="sb-eyebrow">Danh mục</span><h2>Khám phá theo thể loại</h2></div></div>
    <div class="ms-cats">
        @php
            $icons = ['van-hoc' => 'fa-feather-alt', 'kinh-te' => 'fa-chart-line', 'ky-nang-song' => 'fa-lightbulb', 'thieu-nhi' => 'fa-child', 'khoa-hoc' => 'fa-atom',
                      'lich-su' => 'fa-landmark', 'manga' => 'fa-torii-gate', 'manhwa' => 'fa-bolt', 'manhua' => 'fa-yin-yang', 'comic' => 'fa-mask', 'truyen-tranh-viet' => 'fa-star'];
        @endphp
        @foreach($categories as $root)
            @foreach($root->children as $c)
                <a class="ms-cat" href="{{ route('books.category', $c->slug) }}"><i class="fas {{ $icons[$c->slug] ?? 'fa-book' }}"></i><strong>{{ $c->name }}</strong><small>{{ $c->books_count }} đầu sách</small></a>
            @endforeach
        @endforeach
    </div>
</section>

{{-- 10. VỀ BenBooks --}}
<section class="sb-section reveal">
    <div class="sb-card sb-about-mini">
        <div>
            <span class="sb-eyebrow">Về BenBooks</span>
            <h2>Chọn sách không còn là chuyện đau đầu</h2>
            <p>BenBooks gợi ý sách theo sở thích của bạn, cho phép tìm bằng câu nói tự nhiên ("truyện tranh hành động dưới 50k") và hiển thị đánh giá thật từ người đã mua. Không quảng cáo nhảy ra, chỉ có sách hay.</p>
            <a class="sb-btn sb-btn-grad" href="{{ route('policies') }}">Tìm hiểu thêm <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="sb-stats">
            <div class="sb-stat"><strong>{{ $stats['books'] }}</strong><span>Đầu sách</span></div>
            <div class="sb-stat"><strong>{{ $stats['authors'] }}</strong><span>Tác giả</span></div>
            <div class="sb-stat"><strong>{{ $stats['customers'] }}</strong><span>Khách hàng</span></div>
        </div>
    </div>
</section>
@endsection
