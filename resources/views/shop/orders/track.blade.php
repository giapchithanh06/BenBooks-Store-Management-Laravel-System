@extends('layouts.shop')
@section('title', 'Tra cứu đơn hàng')

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-search-location"></i> Không cần đăng nhập', 'title' => 'Tra cứu đơn hàng', 'desc' => 'Nhập mã đơn hàng và số điện thoại nhận hàng.'])
<div class="mx-auto" style="max-width:860px">
    <form class="sb-card sb-card-pad mb-4">
        <div class="row align-items-end">
            <div class="col-md-5 sb-field mb-md-0"><label>Mã đơn hàng</label><div class="sb-input-icon"><i class="fas fa-receipt"></i><input class="sb-control" name="code" value="{{ request('code') }}" placeholder="VD: MS261026ABCDE" required></div></div>
            <div class="col-md-4 sb-field mb-md-0"><label>Số điện thoại</label><div class="sb-input-icon"><i class="fas fa-phone"></i><input class="sb-control" name="phone" value="{{ request('phone') }}" required></div></div>
            <div class="col-md-3"><button class="sb-btn sb-btn-lg sb-btn-block"><i class="fas fa-search"></i> Tra cứu</button></div>
        </div>
    </form>
    @if($order)
        <div class="sb-card sb-card-pad mb-4">
            <div class="sb-box-head"><h3>Đơn #{{ $order->code }}</h3>@include('partials.status')</div>
            @include('shop.orders._steps')
            <div class="sb-sum-row mt-3"><span>Người nhận</span><span>{{ $order->receiver_name }}</span></div>
            <div class="sb-sum-row"><span>Tổng tiền</span><span class="sb-price">{{ money($order->total) }}</span></div>
            <div class="sb-sum-row"><span>Thanh toán</span><span>{{ $order->payment_status_name }}</span></div>
            @if($order->tracking_code)<div class="sb-sum-row"><span>Vận chuyển</span><span>{{ $order->shipping_carrier }} – {{ $order->tracking_code }}</span></div>@endif
        </div>
        <div class="sb-card sb-card-pad">
            <ul class="sb-timeline-v">
                @foreach($order->histories->reverse()->values() as $i => $h)
                    <li data-so="{{ $order->histories->count() - $i }}"><b>{{ $h->status_name }}</b><span>{{ $h->note }}</span><small>{{ $h->created_at->format('H:i d/m/Y') }}</small></li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
