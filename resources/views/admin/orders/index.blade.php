@extends('layouts.admin')
@section('title', 'Đơn hàng')

@section('content')
<ul class="nav nav-pills small mb-3">
    <li class="nav-item"><a class="nav-link {{ !request('status') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">Tất cả <span class="badge text-bg-light">{{ collect($counts)->sum() }}</span></a></li>
    @foreach(\App\Models\Order::STATUSES as $k => $l)
        <li class="nav-item"><a class="nav-link {{ request('status') == $k ? 'active' : '' }}" href="{{ route('admin.orders.index', ['status' => $k]) }}">{{ $l }} <span class="badge text-bg-light">{{ $counts[$k] ?? 0 }}</span></a></li>
    @endforeach
</ul>
<div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Mã đơn, tên, SĐT"></div>
        <div class="col-md-2"><select name="payment_status" class="form-select form-select-sm"><option value="">Thanh toán</option>@foreach(\App\Models\Order::PAYMENT_STATUSES as $k => $l)<option value="{{ $k }}" @if(request('payment_status') == $k) selected @endif>{{ $l }}</option>@endforeach</select></div>
        <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm"></div>
        <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm"></div>
        <div class="col-md-2 form-check pt-1 ms-2"><input type="checkbox" name="risky" value="1" class="form-check-input" id="risky" @if(request('risky')) checked @endif><label for="risky" class="form-check-label small">Nghi bất thường</label></div>
        <div class="col-md-auto"><button class="btn btn-sm btn-secondary">Lọc</button></div>
    </form>
    <div class="table-responsive">
    <table class="table table-hover align-middle small">
        <thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Khách hàng</th><th class="text-center">SP</th><th class="text-end">Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Rủi ro</th><th></th></tr></thead>
        @forelse($orders as $o)
            <tr>
                <td><a href="{{ route('admin.orders.show', $o) }}" class="fw-bold">{{ $o->code }}</a></td>
                <td>{{ $o->created_at->format('H:i d/m/Y') }}</td>
                <td>{{ $o->receiver_name }}<div class="text-muted">{{ $o->phone }}</div></td>
                <td class="text-center">{{ $o->items_count }}</td>
                <td class="text-end fw-semibold">{{ money($o->total) }}</td>
                <td><span class="badge text-bg-light border">{{ strtoupper($o->payment_method) }}</span> <span class="badge text-bg-{{ $o->payment_status === 'paid' ? 'success' : 'warning' }}">{{ $o->payment_status_name }}</span></td>
                <td><span class="badge text-bg-{{ $o->status_color }}">{{ $o->status_name }}</span></td>
                <td>@if($o->fraud_score > 0)<span class="badge text-bg-{{ $o->fraud_score >= 50 ? 'danger' : 'secondary' }}" title="{{ $o->fraud_flags }}">{{ $o->fraud_score }}</span>@endif</td>
                <td><a href="{{ route('admin.orders.show', $o) }}" class="btn btn-sm btn-outline-primary">Xử lý</a></td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-muted">Không có đơn hàng.</td></tr>
        @endforelse
    </table>
    </div>
    {{ $orders->links('partials.pagination') }}
</div></div>
@endsection
