@extends('layouts.shop')
@section('title', 'Giỏ hàng')

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-shopping-cart"></i> Giỏ hàng', 'title' => 'Giỏ hàng của bạn'])
@include('shop._steps-cart', ['step' => 1])

@if($items->isEmpty())
    <div class="sb-card sb-empty">
        <i class="fas fa-shopping-basket"></i>
        <h3>Giỏ hàng đang trống</h3>
        <p>Hàng trăm đầu sách và truyện tranh đang chờ bạn.</p>
        <a class="sb-btn" href="{{ route('books.index') }}">Tiếp tục mua sắm <i class="fas fa-arrow-right"></i></a>
    </div>
@else
<div class="sb-checkout">
    <div class="sb-card sb-card-pad">
        <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-book"></i></span> {{ $items->sum('quantity') }} sản phẩm</h3>
            <a href="{{ route('books.index') }}" class="small fw-bold"><i class="fas fa-arrow-left"></i> Mua thêm</a></div>
        <form action="{{ route('cart.update') }}" method="post" id="cartForm">
            @csrf
            @foreach($items as $it)
                <div class="sb-cart-item">
                    <a class="sb-cart-thumb" href="{{ route('books.show', $it->book->slug) }}"><img src="{{ $it->book->cover_url }}" alt=""></a>
                    <div class="sb-cart-info">
                        <h4><a class="text-reset" href="{{ route('books.show', $it->book->slug) }}">{{ $it->book->title }}</a></h4>
                        <div class="meta">{{ $it->variant->name }} · {{ $it->book->author_names }} @if($it->flash_item)<span class="sb-pill flash"><i class="fas fa-bolt"></i> Flash Sale</span>@endif</div>
                        <div class="mt-1"><span class="sb-price">{{ money($it->price) }}</span> @if($it->compare)<span class="sb-old-price">{{ money($it->compare) }}</span>@endif</div>
                        @if($it->error)<span class="sb-error">{{ $it->error }}</span>@endif
                    </div>
                    <div class="sb-cart-side">
                        <div class="sb-qty" data-qty>
                            <button type="button" data-step="-1" aria-label="Giảm">−</button>
                            <input type="number" name="qty[{{ $it->variant->id }}]" value="{{ $it->quantity }}" min="0" max="{{ $it->variant->stock }}" onchange="this.form.submit()" aria-label="Số lượng">
                            <button type="button" data-step="1" aria-label="Tăng">+</button>
                        </div>
                        <b class="sb-price">{{ money($it->total) }}</b>
                        <button form="rm{{ $it->variant->id }}" class="sb-btn sb-btn-outline sb-btn-sm" title="Xóa"><i class="fas fa-trash-alt"></i> Xóa</button>
                    </div>
                </div>
            @endforeach
        </form>
        @foreach($items as $it)
            <form id="rm{{ $it->variant->id }}" action="{{ route('cart.remove', $it->variant->id) }}" method="post" class="d-none">@csrf</form>
        @endforeach
    </div>

    <aside class="sb-sticky">
        <div class="sb-card sb-card-pad mb-4">
            <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-ticket-alt"></i></span> Mã giảm giá</h3></div>
            @if($voucher)
                <div class="sb-note-box d-flex justify-content-between align-items-center">
                    <div><b>{{ $voucher->code }}</b><br>{{ $voucher->description }}</div>
                    <form action="{{ route('cart.voucher.remove') }}" method="post">@csrf<button class="sb-btn sb-btn-outline sb-btn-sm">Bỏ</button></form>
                </div>
            @else
                <form action="{{ route('cart.voucher') }}" method="post" class="sb-coupon">
                    @csrf
                    <input class="sb-control" name="code" placeholder="Nhập mã" aria-label="Mã giảm giá">
                    <button class="sb-btn">Áp dụng</button>
                </form>
                @if($voucherCode)<div class="sb-coupon-msg loi">Mã {{ $voucherCode }} chưa đủ điều kiện áp dụng.</div>@endif
                @if($availableVouchers->count())
                    <div class="sb-coupon-list">
                        @foreach($availableVouchers as $av)
                            <form action="{{ route('cart.voucher') }}" method="post">@csrf<input type="hidden" name="code" value="{{ $av->code }}"><button title="{{ $av->description }}">{{ $av->code }}</button></form>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
        <div class="sb-card sb-card-pad">
            <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-receipt"></i></span> Tổng đơn hàng</h3></div>
            @if($subtotal < $freeShipFrom)
                <div class="sb-freeship mb-3">Mua thêm <b>{{ money($freeShipFrom - $subtotal) }}</b> để được miễn phí giao hàng
                    <div class="bar"><span style="width: {{ min(100, $subtotal * 100 / $freeShipFrom) }}%"></span></div></div>
            @else
                <div class="sb-freeship mb-3"><i class="fas fa-check-circle" style="color: var(--sb-green)"></i> Đơn hàng được <b>miễn phí giao hàng</b></div>
            @endif
            <div class="sb-sum-row"><span>Tạm tính</span><span>{{ money($subtotal) }}</span></div>
            @if($discount)<div class="sb-sum-row giam"><span>Giảm giá</span><span>−{{ money($discount) }}</span></div>@endif
            <div class="sb-sum-row"><span>Phí giao hàng (dự kiến)</span><span>{{ $shipping ? money($shipping) : 'Miễn phí' }}</span></div>
            <div class="sb-sum-total"><span class="fw-bold">Tổng cộng</span><strong>{{ money(max(0, $subtotal - $discount + $shipping)) }}</strong></div>
            <a class="sb-btn sb-btn-lg sb-btn-block mt-3" href="{{ route('checkout.index') }}">Tiến hành thanh toán <i class="fas fa-arrow-right"></i></a>
            @guest<p class="small text-muted text-center mt-2 mb-0">Bạn cần đăng nhập để đặt hàng – giỏ hàng được giữ nguyên.</p>@endguest
        </div>
    </aside>
</div>
@endif
@endsection
