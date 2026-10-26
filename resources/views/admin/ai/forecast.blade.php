@extends('layouts.admin')
@section('title', 'Dự báo doanh thu & nhu cầu sản phẩm')

@section('content')
<div class="alert alert-light border small">
    <strong>Mô hình:</strong> hồi quy tuyến tính theo thời gian (bình phương tối thiểu) kết hợp trung bình trượt 3 kỳ:
    <code>dự báo = 0.6 × xu hướng + 0.4 × TB 3 kỳ gần nhất</code>. Hệ số xác định R² = <strong>{{ $revenue['r2'] }}</strong>,
    xu hướng {{ $revenue['slope'] >= 0 ? 'tăng' : 'giảm' }} khoảng <strong>{{ money(abs($revenue['slope'])) }}/tháng</strong>.
</div>
<div class="card mb-3"><div class="card-body">
    <h2 class="h6 fw-bold">Doanh thu 12 tháng & dự báo 3 tháng tới</h2>
    <canvas id="fcChart" height="90"></canvas>
    <div class="d-flex gap-3 mt-2 small">@foreach($revenue['future_labels'] as $i => $l)<span class="badge text-bg-warning">{{ $l }}: {{ money($revenue['forecast'][$i]) }}</span>@endforeach</div>
</div></div>
<div class="card"><div class="card-body">
    <h2 class="h6 fw-bold">Dự đoán nhu cầu 4 tuần tới theo đầu sách & đề xuất nhập hàng</h2>
    <div class="table-responsive">
    <table class="table table-sm small align-middle">
        <thead><tr><th>Sách</th><th>Bán 8 tuần (theo tuần)</th><th class="text-end">Tổng</th><th>Xu hướng</th><th class="text-end">Dự báo 4 tuần</th><th class="text-end">Tồn</th><th class="text-end">Đủ bán (tuần)</th><th class="text-end">Đề xuất nhập</th></tr></thead>
        @forelse($demand as $d)
            <tr>
                <td>{{ $d['book']->title }}</td>
                <td><code>{{ implode(' · ', $d['history']) }}</code></td>
                <td class="text-end">{{ $d['sold'] }}</td>
                <td>@if($d['trend'] === 'up')<span class="text-success"><i class="fas fa-arrow-up"></i> Tăng</span>@elseif($d['trend'] === 'down')<span class="text-danger"><i class="fas fa-arrow-down"></i> Giảm</span>@else<span class="text-muted">Ổn định</span>@endif</td>
                <td class="text-end fw-bold">{{ $d['forecast'] }}</td>
                <td class="text-end">{{ $d['stock'] }}</td>
                <td class="text-end">{{ $d['weeks_cover'] ?? '∞' }}</td>
                <td class="text-end">@if($d['suggest'] > 0)<span class="badge text-bg-danger">+{{ $d['suggest'] }}</span>@else<span class="text-muted">—</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-muted">Chưa đủ dữ liệu bán hàng.</td></tr>
        @endforelse
    </table>
    </div>
</div></div>
@endsection

@push('scripts')
@php
    $allLabels = array_merge($revenue['labels'], $revenue['future_labels']);
    $actual = array_merge($revenue['values'], array_fill(0, count($revenue['future_labels']), null));
    $pred = array_merge(array_fill(0, count($revenue['values']) - 1, null), [end($revenue['values'])], $revenue['forecast']);
@endphp
<script>
new Chart(document.getElementById('fcChart'), {type: 'line', data: {labels: @json($allLabels), datasets: [
    {label: 'Thực tế', data: @json($actual), borderColor: '#2F6BFF', backgroundColor: '#2F6BFF22', fill: true, tension: .3},
    {label: 'Dự báo', data: @json($pred), borderColor: '#FF6A1A', borderDash: [6, 4], tension: .3}
]}, options: {scales: {y: {ticks: {callback: function (v) { return (v / 1000000).toLocaleString('vi-VN') + 'tr'; }}}}}});
</script>
@endpush
