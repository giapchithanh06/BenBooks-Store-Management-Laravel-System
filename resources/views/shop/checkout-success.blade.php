@extends('layouts.shop')
@section('title', 'Đặt hàng thành công')

@section('content')
<div style="height:40px"></div>
@include('shop._steps-cart', ['step' => 3])
<div class="sb-card sb-card-pad text-center mx-auto" style="max-width:720px">
    <span class="sb-success-icon"><i class="fas fa-check"></i></span>
    <h1 class="mt-4" style="font-size:2rem">Đặt hàng thành công!</h1>
    <p class="text-muted">Cảm ơn bạn đã mua sách tại BenBooks. Mã đơn hàng của bạn là</p>
    <div class="sb-voucher-chip mb-3" style="font-size:1.1rem"><i class="fas fa-receipt"></i> {{ $order->code }}</div>
    <div class="sb-sum-row"><span>Tổng thanh toán</span><span class="sb-price">{{ money($order->total) }}</span></div>
    <div class="sb-sum-row"><span>Phương thức</span><span>{{ $order->payment_method_name }}</span></div>
    <div class="sb-sum-row"><span>Thanh toán</span><span class="sb-status {{ $order->payment_status === 'paid' ? 'xong' : 'cho' }}">{{ $order->payment_status_name }}</span></div>
    <p class="small text-muted mt-3">Bạn sẽ nhận được thông báo khi đơn hàng được xác nhận và giao cho đơn vị vận chuyển.</p>
    <div class="d-flex gap-2 justify-content-center flex-wrap">
        <a class="sb-btn" href="{{ route('orders.show', $order->code) }}"><i class="fas fa-truck"></i> Theo dõi đơn hàng</a>
        <a class="sb-btn sb-btn-soft" href="{{ route('home') }}">Tiếp tục mua sắm</a>
    </div>
</div>
@endsection
