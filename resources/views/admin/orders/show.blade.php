@extends('layouts.admin')
@section('title', 'Đơn hàng ' . $order->code)

@section('content')
@if($order->fraud_score >= 30)
    <div class="alert alert-{{ $order->fraud_score >= 50 ? 'danger' : 'warning' }}"><i class="fas fa-shield-alt"></i> <strong>Điểm rủi ro {{ $order->fraud_score }}/100.</strong> {{ $order->fraud_flags }}</div>
@endif
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                <div><span class="fs-5 fw-bold">{{ $order->code }}</span> <span class="badge text-bg-{{ $order->status_color }}">{{ $order->status_name }}</span></div>
                <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i> In hóa đơn</a>
            </div>
            <table class="table table-sm small align-middle">
                <thead><tr><th>Sản phẩm</th><th class="text-end">Đơn giá</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th></tr></thead>
                @foreach($order->items as $it)
                    <tr><td>{{ $it->book_title }} <span class="text-muted">({{ $it->variant_name }})</span>@if($it->flash_sale_item_id) <span class="badge badge-flash">FLASH</span>@endif</td>
                        <td class="text-end">{{ money($it->price) }}</td><td class="text-center">{{ $it->quantity }}</td><td class="text-end">{{ money($it->total) }}</td></tr>
                @endforeach
                <tr><td colspan="3" class="text-end">Tạm tính</td><td class="text-end">{{ money($order->subtotal) }}</td></tr>
                <tr><td colspan="3" class="text-end">Giảm giá {{ $order->voucher_code }}</td><td class="text-end text-success">-{{ money($order->discount) }}</td></tr>
                <tr><td colspan="3" class="text-end">Phí ship</td><td class="text-end">{{ money($order->shipping_fee) }}</td></tr>
                <tr class="fw-bold"><td colspan="3" class="text-end">Tổng</td><td class="text-end">{{ money($order->total) }}</td></tr>
            </table>
        </div></div>
        <div class="card mt-3"><div class="card-body small">
            <h2 class="h6 fw-bold">Lịch sử xử lý</h2>
            <ul class="timeline mb-0">
                @foreach($order->histories->reverse() as $h)
                    <li><strong>{{ $h->status_name }}</strong> · {{ $h->created_at->format('H:i d/m/Y') }} · <span class="text-muted">{{ optional($h->user)->name ?: 'Hệ thống' }}</span>@if($h->note)<div>{{ $h->note }}</div>@endif</li>
                @endforeach
            </ul>
        </div></div>
        @if($order->payments->count())
            <div class="card mt-3"><div class="card-body small">
                <h2 class="h6 fw-bold">Giao dịch thanh toán</h2>
                <table class="table table-sm mb-0">
                    @foreach($order->payments as $p)<tr><td><code>{{ $p->transaction_code }}</code></td><td>{{ $p->bank_code }}</td><td>{{ money($p->amount) }}</td><td><span class="badge text-bg-{{ $p->status === 'success' ? 'success' : ($p->status === 'failed' ? 'danger' : 'secondary') }}">{{ $p->status }}</span></td><td>{{ $p->created_at->format('H:i d/m') }}</td></tr>@endforeach
                </table>
            </div></div>
        @endif
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-body small">
            <h2 class="h6 fw-bold">Khách hàng</h2>
            <div><strong>{{ $order->receiver_name }}</strong> · {{ $order->phone }}</div>
            <div>{{ $order->address }}</div>
            @if($order->user)<div class="text-muted mt-1">Tài khoản: {{ $order->user->email }} @if($order->user->segment)<span class="badge text-bg-light border">{{ $order->user->segment }}</span>@endif</div>@endif
            @if($order->note)<div class="alert alert-warning py-1 mt-2 mb-0">{{ $order->note }}</div>@endif
            <hr>
            <div>{{ $order->payment_method_name }}</div>
            <div>Thanh toán: <span class="badge text-bg-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }}">{{ $order->payment_status_name }}</span></div>
        </div></div>

        @php $next = \App\Models\Order::FLOW[$order->status] ?? []; @endphp
        @if($next)
            <form method="post" action="{{ route('admin.orders.status', $order) }}" class="card mt-3"><div class="card-body">
                @csrf
                <h2 class="h6 fw-bold">Cập nhật trạng thái</h2>
                <select name="status" class="form-select form-select-sm mb-2">
                    @foreach($next as $s)<option value="{{ $s }}">{{ \App\Models\Order::STATUSES[$s] }}</option>@endforeach
                </select>
                <input name="note" class="form-control form-control-sm mb-2" placeholder="Ghi chú (gửi kèm thông báo cho khách)">
                <button class="btn btn-primary btn-sm w-100">Cập nhật</button>
            </div></form>
        @endif

        <form method="post" action="{{ route('admin.orders.shipping', $order) }}" class="card mt-3"><div class="card-body">
            @csrf
            <h2 class="h6 fw-bold"><i class="fas fa-truck"></i> Giao vận</h2>
            <label class="form-label small mb-1">Shipper (app giao hàng)</label>
            <select name="shipper_id" class="form-select form-select-sm mb-2">
                <option value="">— Chưa phân công —</option>
                @foreach($shippers as $s)<option value="{{ $s->id }}" @if($order->shipper_id == $s->id) selected @endif>{{ $s->name }} · {{ $s->phone }}</option>@endforeach
            </select>
            <label class="form-label small mb-1">Đơn vị vận chuyển</label>
            <select name="shipping_carrier" class="form-select form-select-sm mb-2">
                <option value="">—</option>
                @foreach($carriers as $c)<option @if($order->shipping_carrier == $c) selected @endif>{{ $c }}</option>@endforeach
            </select>
            <label class="form-label small mb-1">Mã vận đơn</label>
            <input name="tracking_code" value="{{ $order->tracking_code }}" class="form-control form-control-sm mb-2" placeholder="Để trống sẽ tự sinh khi phân công shipper">
            <button class="btn btn-outline-primary btn-sm w-100">Lưu giao vận</button>
        </div></form>
    </div>
</div>
@endsection
