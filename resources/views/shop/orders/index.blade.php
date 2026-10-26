@extends('layouts.shop')
@section('title', 'Đơn hàng của tôi')

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-receipt"></i> Tài khoản', 'title' => 'Đơn hàng của tôi'])
<div class="sb-account">
    @include('shop.account._nav')
    <div>
        <div class="sb-chip-list mb-4">
            <a class="sb-chip {{ !$status ? 'active' : '' }}" href="{{ route('orders.index') }}">Tất cả <span class="sb-chip-count">{{ collect($counts)->sum() }}</span></a>
            @foreach(\App\Models\Order::STATUSES as $k => $label)
                <a class="sb-chip {{ $status === $k ? 'active' : '' }}" href="{{ route('orders.index', ['status' => $k]) }}">{{ $label }} @if(!empty($counts[$k]))<span class="sb-chip-count">{{ $counts[$k] }}</span>@endif</a>
            @endforeach
        </div>
        @forelse($orders as $order)
            <div class="sb-card sb-order-card">
                <div class="sb-order-card-head">
                    <div><b style="font-family: var(--sb-display)">#{{ $order->code }}</b> <span class="text-muted small">· {{ $order->created_at->format('H:i d/m/Y') }}</span></div>
                    <div class="d-flex gap-2">@include('partials.status')<span class="sb-pill {{ $order->payment_status === 'paid' ? 'ok' : '' }}">{{ $order->payment_status_name }}</span></div>
                </div>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="sb-order-thumbs">
                        @foreach($order->items->take(4) as $it)<img src="{{ optional($it->book)->cover_url ?: asset('images/no-cover.png') }}" alt="{{ $it->book_title }}" title="{{ $it->book_title }}">@endforeach
                        @if($order->items->count() > 4)<span class="them">+{{ $order->items->count() - 4 }}</span>@endif
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">{{ $order->items->sum('quantity') }} sản phẩm · Tổng tiền</div>
                        <span class="sb-price" style="font-size:1.3rem">{{ money($order->total) }}</span>
                    </div>
                    <a class="sb-btn sb-btn-soft sb-btn-sm" href="{{ route('orders.show', $order->code) }}">Xem chi tiết <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        @empty
            <div class="sb-card sb-empty"><i class="fas fa-receipt"></i><h3>Chưa có đơn hàng</h3><a class="sb-btn" href="{{ route('books.index') }}">Mua sắm ngay</a></div>
        @endforelse
        {{ $orders->links('partials.pagination') }}
    </div>
</div>
@endsection
