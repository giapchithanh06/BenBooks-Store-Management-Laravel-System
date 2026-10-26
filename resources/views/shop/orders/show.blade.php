@extends('layouts.shop')
@section('title', 'Đơn hàng ' . $order->code)

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-receipt"></i> Chi tiết đơn hàng', 'title' => '#' . $order->code, 'desc' => 'Đặt lúc ' . $order->created_at->format('H:i d/m/Y')])
<div class="sb-account">
    @include('shop.account._nav')
    <div>
        <div class="sb-card sb-card-pad mb-4">
            <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-truck"></i></span> Trạng thái đơn hàng</h3>@include('partials.status')</div>
            @include('shop.orders._steps')
        </div>

        @if($order->needsOnlinePayment())
            <div class="sb-alert warn"><i class="fas fa-exclamation-circle mt-1"></i><div class="flex-grow-1">Đơn hàng chưa được thanh toán online.</div>
                <form action="{{ route('orders.repay', $order->code) }}" method="post">@csrf<button class="sb-btn sb-btn-sm"><i class="fas fa-credit-card"></i> Thanh toán ngay</button></form></div>
        @endif

        <div class="row g-4 mb-4">
            <div class="col-md-6"><div class="sb-card sb-card-pad h-100">
                <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-map-marker-alt"></i></span> Nhận hàng</h3></div>
                <dl class="ad-kv" style="display:grid;grid-template-columns:110px 1fr;gap:6px 12px">
                    <dt class="text-muted">Người nhận</dt><dd>{{ $order->receiver_name }}</dd>
                    <dt class="text-muted">Điện thoại</dt><dd>{{ $order->phone }}</dd>
                    <dt class="text-muted">Địa chỉ</dt><dd>{{ $order->address }}</dd>
                    @if($order->note)<dt class="text-muted">Ghi chú</dt><dd>{{ $order->note }}</dd>@endif
                    @if($order->tracking_code)<dt class="text-muted">Vận chuyển</dt><dd>{{ $order->shipping_carrier }} – <b>{{ $order->tracking_code }}</b></dd>@endif
                </dl>
            </div></div>
            <div class="col-md-6"><div class="sb-card sb-card-pad h-100">
                <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-wallet"></i></span> Thanh toán</h3></div>
                <dl style="display:grid;grid-template-columns:110px 1fr;gap:6px 12px">
                    <dt class="text-muted">Phương thức</dt><dd>{{ $order->payment_method_name }}</dd>
                    <dt class="text-muted">Trạng thái</dt><dd><span class="sb-pill {{ $order->payment_status === 'paid' ? 'ok' : 'warn' }}">{{ $order->payment_status_name }}</span></dd>
                    @if($order->paid_at)<dt class="text-muted">Thời gian</dt><dd>{{ $order->paid_at->format('H:i d/m/Y') }}</dd>@endif
                </dl>
            </div></div>
        </div>

        <div class="sb-card sb-card-pad mb-4">
            <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-book"></i></span> Sản phẩm</h3></div>
            @foreach($order->items as $it)
                <div class="sb-cart-item">
                    <span class="sb-cart-thumb"><img src="{{ optional($it->book)->cover_url ?: asset('images/no-cover.png') }}" alt=""></span>
                    <div class="sb-cart-info">
                        <h4>@if($it->book)<a class="text-reset" href="{{ route('books.show', $it->book->slug) }}">{{ $it->book_title }}</a>@else{{ $it->book_title }}@endif</h4>
                        <div class="meta">{{ $it->variant_name }} · {{ money($it->price) }} × {{ $it->quantity }}</div>
                        @if($order->status === 'delivered' && $it->book)<a class="small fw-bold" href="{{ route('books.show', $it->book->slug) }}#reviews"><i class="far fa-star"></i> Viết đánh giá</a>@endif
                    </div>
                    <div class="sb-cart-side"><b class="sb-price">{{ money($it->total) }}</b></div>
                </div>
            @endforeach
            <div class="sb-sum-row mt-2"><span>Tạm tính</span><span>{{ money($order->subtotal) }}</span></div>
            @if($order->discount)<div class="sb-sum-row giam"><span>Giảm giá {{ $order->voucher_code ? '(' . $order->voucher_code . ')' : '' }}</span><span>−{{ money($order->discount) }}</span></div>@endif
            <div class="sb-sum-row"><span>Phí giao hàng</span><span>{{ $order->shipping_fee ? money($order->shipping_fee) : 'Miễn phí' }}</span></div>
            <div class="sb-sum-total"><span class="fw-bold">Tổng cộng</span><strong>{{ money($order->total) }}</strong></div>
            <div class="d-flex gap-2 flex-wrap mt-3">
                @if($order->canCancel())<button class="sb-btn sb-btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="fas fa-times"></i> Hủy đơn</button>@endif
                @if($order->canRequestReturn())<a class="sb-btn sb-btn-outline" href="{{ route('orders.return', $order->code) }}"><i class="fas fa-exchange-alt"></i> Yêu cầu đổi/trả</a>@endif
                <form action="{{ route('orders.reorder', $order->code) }}" method="post">@csrf<button class="sb-btn sb-btn-soft"><i class="fas fa-redo"></i> Mua lại</button></form>
            </div>
        </div>

        @if($order->returnRequests->count())
            <div class="sb-card sb-card-pad mb-4">
                <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-exchange-alt"></i></span> Yêu cầu đổi/trả</h3></div>
                @foreach($order->returnRequests as $rr)
                    <div class="sb-note-box mb-2"><b>{{ $rr->type_name }}</b> · <span class="sb-pill">{{ $rr->status_name }}</span> · {{ $rr->created_at->format('d/m/Y') }}
                        @if($rr->refund_amount) · hoàn {{ money($rr->refund_amount) }}@endif
                        <div>Lý do: {{ $rr->reason }}</div>@if($rr->admin_note)<div>Phản hồi: <b>{{ $rr->admin_note }}</b></div>@endif</div>
                @endforeach
            </div>
        @endif

        <div class="sb-card sb-card-pad">
            <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-history"></i></span> Lịch sử đơn hàng</h3></div>
            <ul class="sb-timeline-v">
                @foreach($order->histories->reverse()->values() as $i => $h)
                    <li data-so="{{ $order->histories->count() - $i }}"><b>{{ $h->status_name }}</b><span>{{ $h->note }}</span><small>{{ $h->created_at->format('H:i d/m/Y') }}</small></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

<div class="modal fade sb-modal" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><form class="modal-content center" method="post" action="{{ route('orders.cancel', $order->code) }}">
        @csrf
        <div class="sb-modal-icon"><i class="fas fa-times"></i></div>
        <h3>Hủy đơn #{{ $order->code }}?</h3>
        <p>Chọn lý do hủy để BenBooks phục vụ bạn tốt hơn.</p>
        <select name="reason" class="sb-control" required>
            <option value="">-- Chọn lý do --</option>
            @foreach(['Muốn thay đổi địa chỉ giao hàng', 'Muốn đổi sản phẩm / số lượng', 'Tìm được giá tốt hơn', 'Đặt nhầm', 'Không còn nhu cầu'] as $r)<option>{{ $r }}</option>@endforeach
        </select>
        <div class="sb-modal-actions"><button type="button" class="sb-btn sb-btn-outline" data-bs-dismiss="modal">Không hủy</button><button class="sb-btn sb-btn-danger">Xác nhận hủy</button></div>
    </form></div>
</div>
@endsection
