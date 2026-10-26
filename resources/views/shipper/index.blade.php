@extends('layouts.shipper')

@section('content')
<div class="row g-2 mb-3">
    <div class="col-6"><div class="sb-stat"><strong>{{ $todayDelivered }}</strong><span>Đã giao hôm nay</span></div></div>
    <div class="col-6"><div class="sb-stat"><strong style="font-size:1.2rem">{{ money($codToCollect) }}</strong><span>Tiền COD cần thu</span></div></div>
</div>
<span class="sb-eyebrow">{{ $tab === 'done' ? 'Đơn đã xử lý' : 'Đơn cần giao' }} ({{ $orders->count() }})</span>
@forelse($orders as $o)
    <a href="{{ route('shipper.show', $o) }}" class="sb-card sb-order-card d-block text-reset mt-2 mb-2">
        <div class="d-flex justify-content-between align-items-center"><b style="font-family: var(--sb-display)">#{{ $o->code }}</b>@include('partials.status', ['order' => $o])</div>
        <div class="small mt-2"><i class="fas fa-user" style="color: var(--sb-primary)"></i> {{ $o->receiver_name }} · <i class="fas fa-phone" style="color: var(--sb-primary)"></i> {{ $o->phone }}</div>
        <div class="small text-muted"><i class="fas fa-map-marker-alt" style="color: var(--sb-primary)"></i> {{ $o->address }}</div>
        <div class="small mt-1">{{ $o->items->sum('quantity') }} cuốn ·
            @if($o->payment_status === 'paid')<span class="sb-pill ok">Đã thanh toán</span>@else<span class="sb-pill bad">Thu COD {{ money($o->total) }}</span>@endif</div>
    </a>
@empty
    <div class="sb-card sb-empty mt-2"><i class="fas fa-inbox"></i><h3>Không có đơn nào</h3></div>
@endforelse
@endsection
