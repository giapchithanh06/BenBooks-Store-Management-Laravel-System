@extends('layouts.admin')
@section('title', 'Tồn kho')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-lg"><div class="kpi"><small class="text-muted">Số phiên bản</small><div class="fs-5 fw-bold">{{ $summary['variants'] }}</div></div></div>
    <div class="col-6 col-lg"><div class="kpi"><small class="text-muted">Tổng số cuốn</small><div class="fs-5 fw-bold">{{ number_format($summary['units']) }}</div></div></div>
    <div class="col-6 col-lg"><div class="kpi"><small class="text-muted">Giá trị tồn (giá bán)</small><div class="fs-5 fw-bold">{{ money($summary['value']) }}</div></div></div>
    <div class="col-6 col-lg"><a href="?filter=low" class="kpi d-block text-decoration-none text-dark"><small class="text-muted">Sắp hết (1–10)</small><div class="fs-5 fw-bold text-warning">{{ $summary['low'] }}</div></a></div>
    <div class="col-6 col-lg"><a href="?filter=out" class="kpi d-block text-decoration-none text-dark"><small class="text-muted">Hết hàng</small><div class="fs-5 fw-bold text-danger">{{ $summary['out'] }}</div></a></div>
</div>
<div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
        <div class="col-md-5"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tên sách / SKU"></div>
        <div class="col-md-3"><select name="filter" class="form-select form-select-sm"><option value="">Tất cả</option><option value="low" @if($filter == 'low') selected @endif>Sắp hết</option><option value="out" @if($filter == 'out') selected @endif>Hết hàng</option></select></div>
        <div class="col-md-2"><button class="btn btn-sm btn-secondary w-100">Lọc</button></div>
        <div class="col-md-2"><a href="{{ route('admin.inventory.history') }}" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-history"></i> Lịch sử kho</a></div>
    </form>
    <table class="table table-sm align-middle small">
        <thead><tr><th>SKU</th><th>Sách</th><th>Phiên bản</th><th class="text-end">Giá</th><th class="text-center">Tồn</th><th style="width:360px">Phiếu kho</th></tr></thead>
        @foreach($variants as $v)
            <tr>
                <td><code>{{ $v->sku }}</code></td>
                <td>{{ $v->book->title }}<div class="text-muted">{{ optional($v->book->category)->name }}</div></td>
                <td>{{ $v->name }}</td>
                <td class="text-end">{{ money($v->price) }}</td>
                <td class="text-center"><span class="badge fs-6 text-bg-{{ $v->stock <= 0 ? 'danger' : ($v->stock <= 10 ? 'warning' : 'success') }}">{{ $v->stock }}</span></td>
                <td>
                    <form method="post" action="{{ route('admin.inventory.import') }}" class="d-flex gap-1">
                        @csrf <input type="hidden" name="variant_id" value="{{ $v->id }}">
                        <select name="type" class="form-select form-select-sm" style="width:110px"><option value="import">Nhập</option><option value="export">Xuất</option><option value="adjust">Kiểm kê =</option></select>
                        <input type="number" name="quantity" min="1" class="form-control form-control-sm" style="width:80px" placeholder="SL" required>
                        <input name="note" class="form-control form-control-sm" placeholder="Ghi chú">
                        <button class="btn btn-sm btn-primary"><i class="fas fa-check"></i></button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
    {{ $variants->links('partials.pagination') }}
</div></div>
@endsection
