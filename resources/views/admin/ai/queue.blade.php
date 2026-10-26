@extends('layouts.admin')
@section('title', 'Hàng đợi (Message Queue)')

@section('content')
<div class="alert alert-light border small">
    Hệ thống dùng <strong>Laravel Queue – database driver</strong> (bảng <code>jobs</code>, <code>failed_jobs</code>) làm message queue: khi có đơn mới, job <code>ProcessNewOrder</code> được đẩy vào hàng đợi để gửi email xác nhận và cập nhật phân cụm khách hàng; khi sửa sách, job <code>RefreshAiData</code> xây lại vector index.
    Chạy worker liên tục bằng lệnh <code>php artisan queue:work database</code>, hoặc bấm nút bên dưới để xử lý ngay.
</div>
<div class="d-flex gap-2 mb-3 flex-wrap">
    <form method="post" action="{{ route('admin.ai.queue.work') }}">@csrf<button class="btn btn-primary"><i class="fas fa-play"></i> Xử lý hàng đợi ({{ $pending->count() }} job)</button></form>
    <form method="post" action="{{ route('admin.ai.queue.dispatch') }}" class="d-flex gap-1">@csrf
        <select name="task" class="form-select"><option value="all">Làm mới toàn bộ dữ liệu AI</option><option value="search_index">Xây lại vector index</option><option value="sentiment">Phân tích lại cảm xúc</option></select>
        <button class="btn btn-outline-primary text-nowrap">Đẩy job</button>
    </form>
</div>
<div class="row g-3">
    <div class="col-lg-6"><div class="card"><div class="card-body small">
        <h2 class="h6 fw-bold">Job đang chờ</h2>
        <table class="table table-sm mb-0"><thead><tr><th>ID</th><th>Job</th><th>Queue</th><th>Lần thử</th><th>Tạo lúc</th></tr></thead>
            @forelse($pending as $j)<tr><td>{{ $j->id }}</td><td>{{ $j->name }}</td><td>{{ $j->queue }}</td><td>{{ $j->attempts }}</td><td>{{ date('H:i:s d/m', $j->created_at) }}</td></tr>@empty<tr><td colspan="5" class="text-muted">Hàng đợi trống</td></tr>@endforelse
        </table>
    </div></div>
    <div class="card mt-3"><div class="card-body small">
        <h2 class="h6 fw-bold">Job lỗi</h2>
        @forelse($failed as $f)<div class="border-bottom py-1"><code>{{ $f->uuid ?? $f->id }}</code> · {{ $f->failed_at }}<div class="text-danger">{{ \Illuminate\Support\Str::limit($f->exception, 160) }}</div></div>@empty<div class="text-muted">Không có</div>@endforelse
    </div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-body small">
        <h2 class="h6 fw-bold">Nhật ký xử lý</h2>
        @forelse($logs as $l)<div class="border-bottom py-1"><span class="text-muted">{{ $l->created_at->format('H:i:s d/m') }}</span> <span class="badge text-bg-secondary">{{ $l->job }}</span> {{ $l->message }}</div>@empty<div class="text-muted">Chưa có</div>@endforelse
    </div></div></div>
</div>
@endsection
