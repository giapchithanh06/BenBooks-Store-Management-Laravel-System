@extends('layouts.admin')
@section('title', $flashSale->exists ? 'Sửa Flash Sale' : 'Tạo Flash Sale')

@section('content')
@php
    $rows = old('items', $flashSale->exists ? $flashSale->items->map(function ($i) {
        return ['variant_id' => $i->variant_id, 'sale_price' => $i->sale_price, 'quantity' => $i->quantity, 'sold' => $i->sold];
    })->all() : [['variant_id' => '', 'sale_price' => '', 'quantity' => 20]]);
@endphp
<form method="post" action="{{ $flashSale->exists ? route('admin.flash-sales.update', $flashSale) : route('admin.flash-sales.store') }}">
    @csrf
    @if($flashSale->exists) @method('PUT') @endif
    <div class="card mb-3"><div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Tên chương trình *</label><input name="name" value="{{ old('name', $flashSale->name) }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Bắt đầu *</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($flashSale->starts_at)->format('Y-m-d\TH:i')) }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Kết thúc *</label><input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($flashSale->ends_at)->format('Y-m-d\TH:i')) }}" class="form-control" required></div>
        <div class="col-12 form-check form-switch ms-2"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="fa" @if(old('is_active', $flashSale->is_active)) checked @endif><label class="form-check-label" for="fa">Kích hoạt</label></div>
    </div></div>
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between mb-2"><h2 class="h6 fw-bold m-0">Sản phẩm tham gia</h2><button type="button" class="btn btn-sm btn-outline-primary" id="addItem"><i class="fas fa-plus"></i> Thêm</button></div>
        <table class="table table-sm small align-middle">
            <thead><tr><th>Sách – phiên bản (giá gốc)</th><th style="width:160px">Giá sale</th><th style="width:120px">Số suất</th><th style="width:80px">Đã bán</th><th></th></tr></thead>
            <tbody id="items">
            @foreach($rows as $i => $r)
                <tr>
                    <td><select name="items[{{ $i }}][variant_id]" class="form-select form-select-sm" required>
                        <option value="">-- Chọn --</option>
                        @foreach($variants as $v)<option value="{{ $v->id }}" @if(($r['variant_id'] ?? '') == $v->id) selected @endif>{{ $v->book->title }} – {{ $v->name }} ({{ money($v->price) }})</option>@endforeach
                    </select></td>
                    <td><input type="number" name="items[{{ $i }}][sale_price]" value="{{ $r['sale_price'] ?? '' }}" class="form-control form-control-sm" required step="1000"></td>
                    <td><input type="number" name="items[{{ $i }}][quantity]" value="{{ $r['quantity'] ?? '' }}" class="form-control form-control-sm" required min="1"></td>
                    <td>{{ $r['sold'] ?? 0 }}</td>
                    <td><button type="button" class="btn btn-sm btn-link text-danger rm"><i class="fas fa-times"></i></button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <template id="tpl">
            <tr>
                <td><select name="items[__i__][variant_id]" class="form-select form-select-sm" required><option value="">-- Chọn --</option>@foreach($variants as $v)<option value="{{ $v->id }}">{{ $v->book->title }} – {{ $v->name }} ({{ money($v->price) }})</option>@endforeach</select></td>
                <td><input type="number" name="items[__i__][sale_price]" class="form-control form-control-sm" required step="1000"></td>
                <td><input type="number" name="items[__i__][quantity]" value="20" class="form-control form-control-sm" required min="1"></td>
                <td>0</td>
                <td><button type="button" class="btn btn-sm btn-link text-danger rm"><i class="fas fa-times"></i></button></td>
            </tr>
        </template>
        <button class="btn btn-primary">Lưu Flash Sale</button>
        <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-link">Hủy</a>
    </div></div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var tbody = document.getElementById('items'), idx = {{ count($rows) + 10 }};
    document.getElementById('addItem').addEventListener('click', function () {
        tbody.insertAdjacentHTML('beforeend', document.getElementById('tpl').innerHTML.replace(/__i__/g, idx++));
    });
    tbody.addEventListener('click', function (e) {
        var b = e.target.closest('.rm');
        if (b && tbody.rows.length > 1) b.closest('tr').remove();
    });
})();
</script>
@endpush
