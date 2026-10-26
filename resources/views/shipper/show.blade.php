@extends('layouts.shipper')
@section('title', $order->code)

@section('content')
<a href="{{ route('shipper.index') }}" class="small fw-bold"><i class="fas fa-arrow-left"></i> Danh sách</a>
<div class="sb-card sb-card-pad mt-2">
    <div class="d-flex justify-content-between align-items-center"><h1 class="h5 m-0">#{{ $order->code }}</h1>@include('partials.status')</div>
    @if($order->tracking_code)<div class="small text-muted">{{ $order->shipping_carrier }} · {{ $order->tracking_code }}</div>@endif
    <hr>
    <div><i class="fas fa-user" style="color: var(--sb-primary)"></i> <b>{{ $order->receiver_name }}</b></div>
    <a href="tel:{{ $order->phone }}" class="sb-btn sb-btn-sm my-2" style="background: var(--sb-green); border-color: var(--sb-green)"><i class="fas fa-phone"></i> Gọi {{ $order->phone }}</a>
    <div class="small"><i class="fas fa-map-marker-alt" style="color: var(--sb-primary)"></i> {{ $order->address }}</div>
    <a class="small fw-bold" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($order->address) }}"><i class="fas fa-map"></i> Mở bản đồ</a>
    @if($order->note)<div class="sb-alert warn mb-0"><i class="fas fa-sticky-note mt-1"></i><div>{{ $order->note }}</div></div>@endif
</div>
<div class="sb-card sb-card-pad mt-3">
    @foreach($order->items as $it)<div class="sb-sum-row"><span>{{ $it->book_title }} <small class="text-muted">({{ $it->variant_name }})</small></span><span>× {{ $it->quantity }}</span></div>@endforeach
    <div class="sb-sum-total"><span class="fw-bold">{{ $order->payment_status === 'paid' ? 'Đã thanh toán online' : 'Cần thu COD' }}</span><strong>{{ money($order->payment_status === 'paid' ? 0 : $order->total) }}</strong></div>
</div>
<div class="d-grid gap-2 mt-3">
    @if($order->status === 'confirmed')
        <form method="post" action="{{ route('shipper.pickup', $order) }}" class="d-grid">@csrf<button class="sb-btn sb-btn-lg"><i class="fas fa-box-open"></i> Đã lấy hàng – bắt đầu giao</button></form>
    @elseif($order->status === 'shipping')
        <form method="post" action="{{ route('shipper.delivered', $order) }}" class="d-grid" onsubmit="return confirm('Xác nhận đã giao thành công{{ $order->payment_status === 'paid' ? '' : ' và đã thu tiền' }}?')">@csrf<button class="sb-btn sb-btn-lg" style="background: var(--sb-green); border-color: var(--sb-green)"><i class="fas fa-check-circle"></i> Giao thành công</button></form>
        <button class="sb-btn sb-btn-outline" type="button" data-bs-toggle="collapse" data-bs-target="#failBox"><i class="fas fa-times-circle"></i> Giao không thành công</button>
        <form method="post" action="{{ route('shipper.failed', $order) }}" class="collapse" id="failBox">@csrf
            <select name="reason" class="sb-control mb-2" required><option value="">-- Lý do --</option>@foreach(['Không liên lạc được khách', 'Khách hẹn giao lại', 'Sai địa chỉ', 'Khách từ chối nhận'] as $r)<option>{{ $r }}</option>@endforeach</select>
            <button class="sb-btn sb-btn-danger sb-btn-block">Ghi nhận</button>
        </form>
    @endif
</div>
<div class="sb-card sb-card-pad mt-3">
    <ul class="sb-timeline-v">
        @foreach($order->histories->reverse()->values() as $i => $h)
            <li data-so="{{ $order->histories->count() - $i }}"><b>{{ $h->status_name }}</b><span>{{ $h->note }}</span><small>{{ $h->created_at->format('H:i d/m') }}</small></li>
        @endforeach
    </ul>
</div>
@endsection
