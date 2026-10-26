@extends('layouts.admin')
@section('title', 'Lịch sử kho')

@section('content')
<div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Tất cả loại phiếu</option>
                @foreach(\App\Models\StockMovement::TYPES as $k => $l)<option value="{{ $k }}" @if(request('type') == $k) selected @endif>{{ $l }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-9 text-end"><a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-link">&laquo; Tồn kho</a></div>
    </form>
    <table class="table table-sm small">
        <thead><tr><th>Thời gian</th><th>Loại</th><th>Sách / phiên bản</th><th class="text-end">SL</th><th class="text-end">Tồn sau</th><th>Ghi chú</th><th>Người thực hiện</th></tr></thead>
        @foreach($movements as $m)
            <tr>
                <td>{{ $m->created_at->format('H:i d/m/Y') }}</td>
                <td><span class="badge text-bg-light border">{{ $m->type_name }}</span></td>
                <td>{{ optional(optional($m->variant)->book)->title }} – {{ optional($m->variant)->name }}</td>
                <td class="text-end fw-bold {{ $m->quantity >= 0 ? 'text-success' : 'text-danger' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
                <td class="text-end">{{ $m->stock_after }}</td>
                <td>{{ $m->note }}</td>
                <td>{{ optional($m->user)->name ?: 'Hệ thống' }}</td>
            </tr>
        @endforeach
    </table>
    {{ $movements->links('partials.pagination') }}
</div></div>
@endsection
