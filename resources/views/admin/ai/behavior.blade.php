@extends('layouts.admin')
@section('title', 'Phân tích hành vi khách hàng')

@section('content')
<form class="mb-3 d-flex gap-2 align-items-center small">
    Khoảng thời gian:
    @foreach([7, 30, 90] as $d)<a href="?days={{ $d }}" class="btn btn-sm {{ $days == $d ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $d }} ngày</a>@endforeach
</form>
<div class="row g-3 mb-3">
    @foreach(\App\Models\UserActivity::ACTIONS as $k => $l)
        <div class="col"><div class="kpi text-center"><small class="text-muted">{{ $l }}</small><div class="fs-4 fw-bold">{{ $actions[$k] ?? 0 }}</div></div></div>
    @endforeach
</div>
<div class="row g-3">
    <div class="col-lg-5"><div class="card h-100"><div class="card-body">
        <h2 class="h6 fw-bold">Phễu chuyển đổi (người dùng/phiên duy nhất)</h2>
        @foreach($funnel as $i => $f)
            <div class="mb-2">
                <div class="d-flex justify-content-between small"><span>{{ $f['label'] }}</span><span><strong>{{ $f['value'] }}</strong> · {{ $f['rate'] }}%</span></div>
                <div class="progress" style="height:22px"><div class="progress-bar" style="width: {{ max(3, $f['rate']) }}%; background: {{ ['#2F6BFF', '#FF6A1A', '#12A15E'][$i] }}"></div></div>
            </div>
        @endforeach
        <p class="small text-muted mb-0">Tỉ lệ = so với số người xem sách. Dữ liệu ghi nhận từ bảng <code>user_activities</code> (xem, tìm kiếm, thêm giỏ, yêu thích, mua).</p>
    </div></div></div>
    <div class="col-lg-7"><div class="card h-100"><div class="card-body">
        <h2 class="h6 fw-bold">Hoạt động theo giờ trong ngày</h2>
        <canvas id="hourChart" height="120"></canvas>
    </div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-body">
        <h2 class="h6 fw-bold">Từ khóa tìm kiếm nhiều nhất</h2>
        <table class="table table-sm small mb-0">@forelse($keywords as $k)<tr><td>{{ $k->keyword }}</td><td class="text-end">{{ $k->c }}</td></tr>@empty<tr><td class="text-muted">Chưa có dữ liệu</td></tr>@endforelse</table>
    </div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-body">
        <h2 class="h6 fw-bold">Mức quan tâm theo thể loại (lượt xem)</h2>
        <canvas id="catChart" height="260"></canvas>
    </div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-body">
        <h2 class="h6 fw-bold">Xem nhiều nhưng ít mua</h2>
        <table class="table table-sm small mb-0">
            <thead><tr><th>Sách</th><th>Xem</th><th>Giỏ</th><th>Mua</th><th>CR</th></tr></thead>
            @forelse($viewedNotBought as $r)<tr><td>{{ $r->book->title }}</td><td>{{ $r->views }}</td><td>{{ $r->carts }}</td><td>{{ $r->buys }}</td><td>{{ $r->conversion }}%</td></tr>@empty<tr><td colspan="5" class="text-muted">Chưa có dữ liệu</td></tr>@endforelse
        </table>
        <p class="small text-muted mt-2 mb-0">Gợi ý: xem lại giá, ảnh bìa, mô tả hoặc đưa vào Flash Sale.</p>
    </div></div></div>
</div>
@endsection

@push('scripts')
@php
    $catLabels = $categories->pluck('name')->values();
    $catValues = $categories->pluck('c')->values();
@endphp
<script>
new Chart(document.getElementById('hourChart'), {type: 'bar', data: {labels: Array.from({length: 24}, function (_, i) { return i + 'h'; }), datasets: [{label: 'Lượt hoạt động', data: @json($hourly), backgroundColor: '#2F6BFFbb'}]}, options: {plugins: {legend: {display: false}}}});
new Chart(document.getElementById('catChart'), {type: 'doughnut', data: {labels: @json($catLabels), datasets: [{data: @json($catValues), backgroundColor: MS_COLORS}]}, options: {plugins: {legend: {position: 'bottom'}}}});
</script>
@endpush
