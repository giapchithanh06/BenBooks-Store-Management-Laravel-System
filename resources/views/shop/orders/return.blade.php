@extends('layouts.shop')
@section('title', 'Đổi/trả hàng')

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-exchange-alt"></i> Đổi / trả hàng', 'title' => 'Yêu cầu đổi/trả đơn #' . $order->code,
    'desc' => 'Áp dụng trong 7 ngày kể từ khi nhận hàng (' . optional($order->delivered_at)->format('d/m/Y') . '). Sách cần còn nguyên tem, không hư hỏng do người dùng.'])
<div class="sb-account">
    @include('shop.account._nav')
    <form method="post" action="{{ route('orders.return.store', $order->code) }}" class="sb-card sb-card-pad">
        @csrf
        <div class="sb-field"><label>Hình thức <span class="req">*</span></label></div>
        <div class="row g-3 mb-3">
            @foreach(\App\Models\ReturnRequest::TYPES as $k => $label)
                <div class="col-md-6"><label class="sb-pay-option mb-0"><input type="radio" name="type" value="{{ $k }}" @if(old('type', 'return') == $k) checked @endif>
                    <span class="ico"><i class="fas {{ $k === 'return' ? 'fa-undo-alt' : 'fa-exchange-alt' }}"></i></span><span><strong>{{ $label }}</strong><small>{{ $k === 'return' ? 'Hoàn tiền về tài khoản/ví' : 'Gửi sách mới thay thế' }}</small></span></label></div>
            @endforeach
        </div>
        <div class="sb-field"><label>Chọn sản phẩm & số lượng <span class="req">*</span></label></div>
        @foreach($order->items as $it)
            <div class="sb-cart-item">
                <span class="sb-cart-thumb"><img src="{{ optional($it->book)->cover_url ?: asset('images/no-cover.png') }}" alt=""></span>
                <div class="sb-cart-info"><h4>{{ $it->book_title }}</h4><div class="meta">{{ $it->variant_name }} · đã mua {{ $it->quantity }}</div></div>
                <div class="sb-cart-side"><div class="sb-qty" data-qty><button type="button" data-step="-1">−</button><input type="number" name="qty[{{ $it->id }}]" value="{{ old('qty.' . $it->id, 0) }}" min="0" max="{{ $it->quantity }}"><button type="button" data-step="1">+</button></div></div>
            </div>
        @endforeach
        <div class="sb-field mt-3"><label>Lý do <span class="req">*</span></label><textarea name="reason" class="sb-control" required placeholder="VD: sách bị rách bìa, lỗi in trang 45, giao nhầm tập...">{{ old('reason') }}</textarea></div>
        <div class="d-flex gap-2"><button class="sb-btn"><i class="fas fa-paper-plane"></i> Gửi yêu cầu</button><a class="sb-btn sb-btn-outline" href="{{ route('orders.show', $order->code) }}">Quay lại</a></div>
    </form>
</div>
@endsection
