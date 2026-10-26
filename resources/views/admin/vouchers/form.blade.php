@extends('layouts.admin')
@section('title', $voucher->exists ? 'Sửa mã giảm giá' : 'Tạo mã giảm giá')

@section('content')
<form method="post" action="{{ $voucher->exists ? route('admin.vouchers.update', $voucher) : route('admin.vouchers.store') }}" class="card" style="max-width:760px"><div class="card-body row g-3">
    @csrf
    @if($voucher->exists) @method('PUT') @endif
    <div class="col-md-4"><label class="form-label">Mã *</label><input name="code" value="{{ old('code', $voucher->code) }}" class="form-control text-uppercase" required></div>
    <div class="col-md-8"><label class="form-label">Tên chương trình *</label><input name="name" value="{{ old('name', $voucher->name) }}" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Loại</label>
        <select name="type" class="form-select"><option value="percent" @if(old('type', $voucher->type) == 'percent') selected @endif>Giảm theo %</option><option value="fixed" @if(old('type', $voucher->type) == 'fixed') selected @endif>Giảm số tiền</option></select></div>
    <div class="col-md-4"><label class="form-label">Giá trị * (% hoặc ₫)</label><input type="number" name="value" value="{{ old('value', $voucher->value) }}" class="form-control" required min="1"></div>
    <div class="col-md-4"><label class="form-label">Giảm tối đa (₫)</label><input type="number" name="max_discount" value="{{ old('max_discount', $voucher->max_discount) }}" class="form-control"></div>
    <div class="col-md-4"><label class="form-label">Đơn tối thiểu (₫)</label><input type="number" name="min_order" value="{{ old('min_order', $voucher->min_order) }}" class="form-control"></div>
    <div class="col-md-4"><label class="form-label">Tổng lượt dùng</label><input type="number" name="usage_limit" value="{{ old('usage_limit', $voucher->usage_limit) }}" class="form-control" placeholder="Không giới hạn"></div>
    <div class="col-md-4"><label class="form-label">Lượt / khách *</label><input type="number" name="per_user_limit" value="{{ old('per_user_limit', $voucher->per_user_limit) }}" class="form-control" required min="1"></div>
    <div class="col-md-6"><label class="form-label">Bắt đầu</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($voucher->starts_at)->format('Y-m-d\TH:i')) }}" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Kết thúc</label><input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($voucher->ends_at)->format('Y-m-d\TH:i')) }}" class="form-control"></div>
    <div class="col-12 form-check form-switch ms-2"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="va" @if(old('is_active', $voucher->is_active)) checked @endif><label class="form-check-label" for="va">Kích hoạt</label></div>
    <div class="col-12"><button class="btn btn-primary">Lưu</button> <a href="{{ route('admin.vouchers.index') }}" class="btn btn-link">Hủy</a></div>
</div></form>
@endsection
