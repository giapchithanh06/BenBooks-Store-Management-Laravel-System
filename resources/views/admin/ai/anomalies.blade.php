@extends('layouts.admin')
@section('title', 'Phát hiện đơn hàng bất thường')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-8"><div class="alert alert-light border small h-100 mb-0">
        <strong>Cách tính điểm rủi ro (0–100):</strong> z-score giá trị đơn so với toàn bộ đơn (&gt;2σ, &gt;3σ) · tài khoản mới tạo &lt; 24h đặt đơn lớn ·
        ≥ 3 đơn trong 1 giờ · tỉ lệ hủy ≥ 50% · SĐT dùng chung nhiều tài khoản · mua ≥ 10 cuốn 1 mã · COD giá trị lớn · giảm giá &gt; 40%.
        Đơn ≥ 50 điểm sẽ gửi cảnh báo realtime cho admin/nhân viên.
    </div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-body small">
        @foreach($dist as $l => $c)<div class="d-flex justify-content-between"><span>{{ $l }}</span><strong>{{ $c }}</strong></div>@endforeach
        <form method="post" action="{{ route('admin.ai.anomalies.scan') }}" class="mt-2">@csrf<button class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-sync-alt"></i> Quét lại toàn bộ đơn</button></form>
    </div></div></div>
</div>
<div class="card"><div class="card-body">
    <div class="mb-2 small">Lọc: <a href="?min=0">Tất cả</a> · <a href="?min=30">≥ 30</a> · <a href="?min=50">≥ 50</a></div>
    <table class="table table-sm small align-middle">
        <thead><tr><th>Đơn</th><th>Khách</th><th class="text-end">Giá trị</th><th>Trạng thái</th><th>Điểm</th><th>Lý do</th></tr></thead>
        @forelse($orders as $o)
            <tr>
                <td><a href="{{ route('admin.orders.show', $o) }}">{{ $o->code }}</a><div class="text-muted">{{ $o->created_at->format('H:i d/m') }}</div></td>
                <td>{{ optional($o->user)->name }}<div class="text-muted">{{ $o->phone }}</div></td>
                <td class="text-end">{{ money($o->total) }}</td>
                <td><span class="badge text-bg-{{ $o->status_color }}">{{ $o->status_name }}</span></td>
                <td><div class="progress" style="width:80px;height:16px"><div class="progress-bar bg-{{ $o->fraud_score >= 50 ? 'danger' : ($o->fraud_score >= 30 ? 'warning' : 'secondary') }}" style="width: {{ $o->fraud_score }}%">{{ $o->fraud_score }}</div></div></td>
                <td>{{ $o->fraud_flags }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted">Không có đơn bất thường.</td></tr>
        @endforelse
    </table>
    {{ $orders->links('partials.pagination') }}
</div></div>
@endsection
