@extends('layouts.admin')
@section('title', 'Bảng điều khiển')
@section('eyebrow', 'Tổng quan · ' . now()->format('d/m/Y'))

@section('content')
<section class="ad-hero">
    <span class="sb-eyebrow" style="color:#FFB27D"><i class="fas fa-hand-peace"></i> Xin chào, {{ auth()->user()->name }}</span>
    <h1>Hôm nay có {{ $kpi['orders_today'] }} đơn mới, {{ $kpi['orders_pending'] }} đơn đang chờ xác nhận.</h1>
    <p>Doanh thu tháng này {{ money($kpi['revenue_month']) }}@if($kpi['growth'] !== null), {{ $kpi['growth'] >= 0 ? 'tăng' : 'giảm' }} {{ abs($kpi['growth']) }}% so với tháng trước @endif.</p>
    <div class="d-flex gap-2 flex-wrap">
        <a class="sb-btn" href="{{ route('admin.orders.index', ['status' => 'pending']) }}"><i class="fas fa-receipt"></i> Xử lý đơn chờ</a>
        <a class="sb-btn sb-btn-white" href="{{ route('admin.books.create') }}"><i class="fas fa-plus"></i> Thêm sách</a>
    </div>
</section>

<div class="ad-stats">
    <div class="ad-stat"><span class="ico"><i class="fas fa-money-bill-wave"></i></span><div><p>Doanh thu hôm nay</p><h3>{{ money($kpi['revenue_today']) }}</h3><span>{{ $kpi['orders_today'] }} đơn</span></div></div>
    <div class="ad-stat"><span class="ico lam"><i class="fas fa-chart-line"></i></span><div><p>Doanh thu tháng</p><h3>{{ money($kpi['revenue_month']) }}</h3>
        @if($kpi['growth'] !== null)<span class="{{ $kpi['growth'] < 0 ? 'canh-bao' : '' }}"><i class="fas {{ $kpi['growth'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i> {{ abs($kpi['growth']) }}% so với tháng trước</span>@endif</div></div>
    <a class="ad-stat" href="{{ route('admin.orders.index', ['status' => 'pending']) }}"><span class="ico cam"><i class="fas fa-clock"></i></span><div><p>Đơn chờ xác nhận</p><h3>{{ $kpi['orders_pending'] }}</h3><span class="{{ $kpi['orders_pending'] ? 'canh-bao' : '' }}">Cần xử lý</span></div></a>
    <div class="ad-stat"><span class="ico"><i class="fas fa-users"></i></span><div><p>Khách hàng</p><h3>{{ $kpi['customers'] }}</h3><span>+{{ $kpi['new_customers'] }} trong tháng</span></div></div>
</div>
<div class="ad-stats">
    <a class="ad-stat" href="{{ route('admin.inventory.index', ['filter' => 'low']) }}"><span class="ico do"><i class="fas fa-boxes"></i></span><div><p>Sắp hết hàng</p><h3>{{ $kpi['low_stock'] }}</h3><span class="canh-bao">phiên bản ≤ 5 cuốn</span></div></a>
    <a class="ad-stat" href="{{ route('admin.returns.index', ['status' => 'pending']) }}"><span class="ico lam"><i class="fas fa-exchange-alt"></i></span><div><p>Đổi / trả chờ duyệt</p><h3>{{ $kpi['returns'] }}</h3><span>yêu cầu</span></div></a>
    <a class="ad-stat" href="{{ auth()->user()->isAdmin() ? route('admin.ai.anomalies', ['min' => 50]) : route('admin.orders.index', ['risky' => 1]) }}"><span class="ico do"><i class="fas fa-shield-alt"></i></span><div><p>Đơn nghi bất thường</p><h3>{{ $kpi['anomalies'] }}</h3><span class="canh-bao">điểm rủi ro ≥ 50</span></div></a>
    <a class="ad-stat" href="{{ route('admin.reviews.index', ['sentiment' => 'negative']) }}"><span class="ico cam"><i class="fas fa-frown"></i></span><div><p>Đánh giá tiêu cực</p><h3>{{ $kpi['negative_reviews'] }}</h3><span>30 ngày qua (AI)</span></div></a>
</div>

<div class="ad-grid">
    <div class="ad-panel">
        <div class="ad-panel-head"><div><span class="sb-eyebrow">30 ngày</span><h2>Doanh thu & số đơn</h2></div><a class="sb-btn sb-btn-soft sb-btn-sm" href="{{ auth()->user()->isAdmin() ? route('admin.reports.index') : route('admin.orders.index') }}">Chi tiết</a></div>
        <canvas id="revChart" height="120"></canvas>
    </div>
    <div class="ad-panel">
        <div class="ad-panel-head"><div><span class="sb-eyebrow">Tỉ lệ</span><h2>Trạng thái đơn hàng</h2></div></div>
        <canvas id="statusChart" height="230"></canvas>
    </div>
</div>

<div class="ad-grid deu">
    <div class="ad-panel">
        <div class="ad-panel-head"><div><span class="sb-eyebrow">30 ngày</span><h2>Sách bán chạy</h2></div></div>
        <div class="ad-list">
            @foreach($topBooks as $i => $b)
                <div class="ad-item"><span class="rank">{{ $i + 1 }}</span><div class="info"><b>{{ $b->book_title }}</b><small>{{ $b->qty }} cuốn</small></div><b class="sb-price">{{ money($b->revenue) }}</b></div>
            @endforeach
        </div>
    </div>
    <div class="ad-panel">
        <div class="ad-panel-head"><div><span class="sb-eyebrow">Mới nhất</span><h2>Đơn hàng gần đây</h2></div><a class="sb-btn sb-btn-soft sb-btn-sm" href="{{ route('admin.orders.index') }}">Tất cả</a></div>
        <div class="ad-list">
            @foreach($recentOrders as $o)
                <a class="ad-item" href="{{ route('admin.orders.show', $o) }}">
                    <span class="icon {{ $o->fraud_score >= 50 ? 'cam' : '' }}"><i class="fas {{ $o->fraud_score >= 50 ? 'fa-shield-alt' : 'fa-receipt' }}"></i></span>
                    <div class="info"><b>#{{ $o->code }} · {{ $o->receiver_name }}</b><small>{{ $o->created_at->diffForHumans() }} · {{ money($o->total) }}</small></div>
                    @include('partials.status', ['order' => $o])
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="ad-panel">
    <div class="ad-panel-head"><div><span class="sb-eyebrow">Kho hàng</span><h2>Sắp hết hàng</h2></div><a class="sb-btn sb-btn-soft sb-btn-sm" href="{{ route('admin.inventory.index', ['filter' => 'low']) }}">Nhập kho</a></div>
    <div class="ad-table-wrap"><table class="ad-table">
        <thead><tr><th>Sách</th><th>Phiên bản</th><th>SKU</th><th class="num">Tồn</th></tr></thead>
        @forelse($lowStock as $v)
            <tr><td><div class="ad-sp"><img src="{{ optional($v->book)->cover_url }}" alt=""><b>{{ optional($v->book)->title }}</b></div></td><td>{{ $v->name }}</td><td>{{ $v->sku }}</td>
                <td class="num"><span class="sb-pill {{ $v->stock <= 0 ? 'bad' : 'warn' }}">{{ $v->stock }}</span></td></tr>
        @empty
            <tr><td colspan="4" class="ad-empty">Kho hàng ổn định.</td></tr>
        @endforelse
    </table></div>
</div>
@endsection

@push('scripts')
@php
    $statusLabels = [];
    $statusData = [];
    foreach ($statusCounts as $k => $c) {
        $statusLabels[] = \App\Models\Order::STATUSES[$k] ?? $k;
        $statusData[] = (int) $c;
    }
@endphp
<script>
new Chart(document.getElementById('revChart'), {
    data: {
        labels: @json($chart['labels']),
        datasets: [
            {type: 'bar', label: 'Doanh thu (₫)', data: @json($chart['revenue']), backgroundColor: '#0B1B33', hoverBackgroundColor: '#2F6BFF', borderRadius: 4, yAxisID: 'y'},
            {type: 'line', label: 'Số đơn', data: @json($chart['orders']), borderColor: '#FF6A1A', backgroundColor: '#FF6A1A', yAxisID: 'y1', tension: .35}
        ]
    },
    options: {interaction: {mode: 'index', intersect: false}, scales: {
        y: {ticks: {callback: function (v) { return (v / 1000).toLocaleString('vi-VN') + 'k'; }}, grid: {color: '#efefef'}},
        y1: {position: 'right', grid: {display: false}, beginAtZero: true},
        x: {grid: {display: false}}
    }}
});
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {labels: @json($statusLabels), datasets: [{data: @json($statusData), backgroundColor: MS_COLORS, borderWidth: 0}]},
    options: {cutout: '62%', plugins: {legend: {position: 'bottom'}}}
});
</script>
@endpush
