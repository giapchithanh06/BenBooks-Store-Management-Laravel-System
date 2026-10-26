@extends('layouts.admin')
@section('title', 'Phân cụm khách hàng (RFM + K-Means)')

@section('content')
<div class="alert alert-light border small">
    <strong>Phương pháp:</strong> mỗi khách hàng được mô tả bằng 3 đặc trưng <strong>R</strong>ecency (số ngày từ lần mua cuối), <strong>F</strong>requency (số đơn), <strong>M</strong>onetary (tổng chi tiêu);
    chuẩn hóa min-max rồi phân thành 4 cụm bằng thuật toán <strong>K-Means</strong>. Nhãn cụm được đặt tự động theo tâm cụm. Kết quả lưu vào <code>users.segment</code> và cập nhật lại sau mỗi đơn hàng (qua hàng đợi).
</div>
<div class="row g-3 mb-3">
    @foreach($stats as $s)
        <div class="col-md-6 col-xl-3"><div class="kpi border-start border-4 border-{{ $s['color'] }}">
            <div class="fw-bold">{{ $s['label'] }}</div>
            <div class="fs-3 fw-bold">{{ $s['count'] }} <small class="fs-6 text-muted">khách</small></div>
            <div class="small text-muted">R TB {{ $s['recency'] }} ngày · F TB {{ $s['frequency'] }} đơn · M TB {{ money($s['monetary']) }}</div>
            <div class="small mt-1"><i class="fas fa-lightbulb text-warning"></i> {{ $s['action'] }}</div>
        </div></div>
    @endforeach
</div>
<div class="row g-3">
    <div class="col-lg-6"><div class="card"><div class="card-body">
        <h2 class="h6 fw-bold">Biểu đồ phân cụm (F × M, kích thước = độ "mới")</h2>
        <canvas id="segChart" height="260"></canvas>
        <p class="small text-muted mb-0">{{ $noOrder }} khách hàng chưa phát sinh đơn hàng.</p>
    </div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-body">
        <h2 class="h6 fw-bold">Chi tiết khách hàng</h2>
        <div style="max-height:420px; overflow:auto">
        <table class="table table-sm small">
            <thead><tr><th>Khách hàng</th><th>Cụm</th><th class="text-end">R</th><th class="text-end">F</th><th class="text-end">M</th></tr></thead>
            @foreach($rows as $r)<tr><td>{{ $r['user']->name }}</td><td>{{ $r['user']->segment }}</td><td class="text-end">{{ $r['recency'] }}</td><td class="text-end">{{ $r['frequency'] }}</td><td class="text-end">{{ money($r['monetary']) }}</td></tr>@endforeach
        </table>
        </div>
    </div></div></div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var pts = @json($points), groups = {};
    pts.forEach(function (p) { (groups[p.segment] = groups[p.segment] || []).push({x: p.x, y: p.y, r: Math.max(4, 16 - Math.min(12, p.r / 10)), name: p.name}); });
    var ds = Object.keys(groups).map(function (k, i) { return {label: k, data: groups[k], backgroundColor: MS_COLORS[i] + 'aa'}; });
    new Chart(document.getElementById('segChart'), {type: 'bubble', data: {datasets: ds}, options: {
        scales: {x: {title: {display: true, text: 'Số đơn (F)'}}, y: {title: {display: true, text: 'Tổng chi tiêu (nghìn ₫)'}}},
        plugins: {tooltip: {callbacks: {label: function (c) { return c.raw.name + ': ' + c.raw.x + ' đơn, ' + c.raw.y + 'k'; }}}}
    }});
})();
</script>
@endpush
