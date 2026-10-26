@extends('layouts.admin')
@section('title', 'Báo cáo bán hàng')

@section('content')
<form class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label small">Từ ngày</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm"></div>
    <div class="col-md-3"><label class="form-label small">Đến ngày</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Xem báo cáo</button></div>
    <div class="col-md-4 text-end">
        <div class="dropdown">
            <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-file-excel"></i> Xuất Excel (CSV)</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach(['books' => 'Theo sách', 'days' => 'Theo ngày', 'categories' => 'Theo thể loại', 'inventory' => 'Tồn kho'] as $t => $l)
                    <li><a class="dropdown-item" href="{{ route('admin.reports.export', ['type' => $t, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">{{ $l }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
</div></form>

<div class="row g-3 mb-3">
    @foreach([['Doanh thu thuần', money($summary['net'])], ['Số đơn', $summary['orders'] . ' (' . $summary['delivered'] . ' đã giao, ' . $summary['cancelled'] . ' hủy)'], ['Số cuốn bán ra', $summary['items']], ['Giá trị TB/đơn', money($summary['aov'])], ['Tiền hàng', money($summary['gross'])], ['Giảm giá', money($summary['discount'])], ['Phí ship', money($summary['shipping'])]] as $c)
        <div class="col-6 col-lg-3"><div class="kpi"><small class="text-muted">{{ $c[0] }}</small><div class="fw-bold fs-6">{{ $c[1] }}</div></div></div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-8"><div class="card"><div class="card-body"><h2 class="h6 fw-bold">Doanh thu theo ngày</h2><canvas id="dayChart" height="120"></canvas></div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body"><h2 class="h6 fw-bold">Theo thể loại</h2><canvas id="catChart" height="240"></canvas></div></div></div>
    <div class="col-lg-7"><div class="card"><div class="card-body">
        <h2 class="h6 fw-bold">Top sách theo doanh thu</h2>
        <table class="table table-sm small"><thead><tr><th>#</th><th>Sách</th><th class="text-end">SL</th><th class="text-end">Doanh thu</th></tr></thead>
            @foreach($byBook as $i => $b)<tr><td>{{ $i + 1 }}</td><td>{{ $b->book_title }}</td><td class="text-end">{{ $b->qty }}</td><td class="text-end">{{ money($b->revenue) }}</td></tr>@endforeach
        </table>
    </div></div></div>
    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <h2 class="h6 fw-bold">Theo phương thức thanh toán</h2>
            <table class="table table-sm small">@foreach($byPayment as $p)<tr><td>{{ \App\Models\Order::PAYMENT_METHODS[$p->payment_method] ?? $p->payment_method }}</td><td class="text-end">{{ $p->c }} đơn</td><td class="text-end">{{ money($p->revenue) }}</td></tr>@endforeach</table>
        </div></div>
        <div class="card mt-3"><div class="card-body">
            <h2 class="h6 fw-bold">Tồn kho thấp nhất</h2>
            <table class="table table-sm small">@foreach($inventory->take(10) as $v)<tr><td>{{ $v->book->title }} – {{ $v->name }}</td><td class="text-end"><span class="badge text-bg-{{ $v->stock <= 5 ? 'danger' : 'light' }}">{{ $v->stock }}</span></td></tr>@endforeach</table>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $dayLabels = $byDay->pluck('d')->map(function ($d) { return \Carbon\Carbon::parse($d)->format('d/m'); })->values();
    $dayValues = $byDay->pluck('revenue')->map(function ($v) { return (int) $v; })->values();
    $catLabels = $byCategory->pluck('name')->values();
    $catValues = $byCategory->pluck('revenue')->map(function ($v) { return (int) $v; })->values();
@endphp
<script>
new Chart(document.getElementById('dayChart'), {type: 'line', data: {labels: @json($dayLabels), datasets: [{label: 'Doanh thu', data: @json($dayValues), borderColor: '#2F6BFF', backgroundColor: '#2F6BFF22', fill: true, tension: .3}]}});
new Chart(document.getElementById('catChart'), {type: 'doughnut', data: {labels: @json($catLabels), datasets: [{data: @json($catValues), backgroundColor: MS_COLORS}]}, options: {plugins: {legend: {position: 'bottom'}}}});
</script>
@endpush
