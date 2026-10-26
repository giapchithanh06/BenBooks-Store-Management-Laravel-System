@extends('layouts.shop')
@section('title', 'Thanh toán')

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-credit-card"></i> Thanh toán', 'title' => 'Đặt hàng & thanh toán'])
@include('shop._steps-cart', ['step' => 2])

<form action="{{ route('checkout.store') }}" method="post" class="sb-checkout">
    @csrf
    <div>
        <div class="sb-card sb-card-pad mb-4">
            <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-map-marker-alt"></i></span> Địa chỉ nhận hàng</h3>
                <a href="{{ route('account.addresses') }}" class="small fw-bold">Quản lý sổ địa chỉ</a></div>
            @foreach($addresses as $a)
                <label class="sb-pay-option">
                    <input type="radio" class="addr-radio" name="address_id" value="{{ $a->id }}" data-province="{{ $a->province }}" @if(old('address_id', $loop->first ? $a->id : null) == $a->id) checked @endif>
                    <span class="ico"><i class="fas fa-home"></i></span>
                    <span><strong>{{ $a->receiver_name }} · {{ $a->phone }} @if($a->is_default)<span class="sb-pill info">Mặc định</span>@endif</strong><small>{{ $a->full }}</small></span>
                </label>
            @endforeach
            <label class="sb-pay-option">
                <input type="radio" class="addr-radio" name="address_id" value="" @if($addresses->isEmpty() || old('address_id') === '') checked @endif>
                <span class="ico"><i class="fas fa-plus"></i></span>
                <span><strong>Giao đến địa chỉ khác</strong><small>Nhập địa chỉ mới bên dưới</small></span>
            </label>
            <div id="newAddress" class="row mt-3">
                <div class="col-md-6 sb-field"><label>Họ tên người nhận <span class="req">*</span></label><div class="sb-input-icon"><i class="fas fa-user"></i><input class="sb-control" name="receiver_name" value="{{ old('receiver_name', auth()->user()->name) }}"></div></div>
                <div class="col-md-6 sb-field"><label>Số điện thoại <span class="req">*</span></label><div class="sb-input-icon"><i class="fas fa-phone"></i><input class="sb-control" name="phone" value="{{ old('phone', auth()->user()->phone) }}"></div></div>
                <div class="col-md-4 sb-field"><label>Tỉnh / Thành phố <span class="req">*</span></label><input class="sb-control" name="province" id="provinceInput" list="provinces" value="{{ old('province') }}"></div>
                <div class="col-md-4 sb-field"><label>Quận / Huyện <span class="req">*</span></label><input class="sb-control" name="district" value="{{ old('district') }}"></div>
                <div class="col-md-4 sb-field"><label>Phường / Xã <span class="req">*</span></label><input class="sb-control" name="ward" value="{{ old('ward') }}"></div>
                <div class="col-12 sb-field"><label>Số nhà, tên đường <span class="req">*</span></label><div class="sb-input-icon"><i class="fas fa-road"></i><input class="sb-control" name="detail" value="{{ old('detail') }}"></div></div>
                <div class="col-12"><label class="sb-check"><input type="checkbox" name="save_address" value="1" checked> Lưu vào sổ địa chỉ</label></div>
                <datalist id="provinces">@foreach(['Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Cần Thơ', 'Hải Phòng', 'Bình Dương', 'Đồng Nai', 'Long An', 'Bà Rịa - Vũng Tàu', 'Tây Ninh', 'Khánh Hòa', 'Lâm Đồng', 'Thừa Thiên Huế', 'Nghệ An'] as $p)<option value="{{ $p }}">@endforeach</datalist>
            </div>
        </div>
        <div class="sb-card sb-card-pad mb-4">
            <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-wallet"></i></span> Phương thức thanh toán</h3></div>
            @foreach($methods as $k => $label)
                <label class="sb-pay-option">
                    <input type="radio" name="payment_method" value="{{ $k }}" @if(old('payment_method', 'cod') == $k) checked @endif>
                    <span class="ico"><i class="fas {{ $k === 'cod' ? 'fa-money-bill-wave' : 'fa-university' }}"></i></span>
                    <span><strong>{{ $label }}</strong><small>{{ $k === 'cod' ? 'Trả tiền mặt khi nhận sách.' : 'Chuyển sang cổng thanh toán (bản sandbox – không trừ tiền thật).' }}</small></span>
                </label>
            @endforeach
            <div class="sb-field mt-3 mb-0"><label>Ghi chú</label><textarea class="sb-control" name="note" placeholder="VD: giao giờ hành chính">{{ old('note') }}</textarea></div>
        </div>
    </div>
    <aside class="sb-sticky">
        <div class="sb-card sb-card-pad">
            <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-receipt"></i></span> Đơn hàng ({{ $items->sum('quantity') }})</h3><a class="small fw-bold" href="{{ route('cart.index') }}">Sửa</a></div>
            @foreach($items as $it)
                <div class="sb-order-line"><img src="{{ $it->book->cover_url }}" alt=""><div><b>{{ $it->book->title }}</b><small>{{ $it->variant->name }} × {{ $it->quantity }}</small></div><span class="fw-bold">{{ money($it->total) }}</span></div>
            @endforeach
            <div class="sb-sum-row mt-2"><span>Tạm tính</span><span>{{ money($subtotal) }}</span></div>
            @if($discount)<div class="sb-sum-row giam"><span>Giảm giá @if($voucher)({{ $voucher->code }})@endif</span><span>−{{ money($discount) }}</span></div>@endif
            <div class="sb-sum-row"><span>Phí giao hàng</span><span id="shipFee">{{ $shipping ? money($shipping) : 'Miễn phí' }}</span></div>
            <div class="sb-sum-total"><span class="fw-bold">Tổng thanh toán</span><strong id="grand">{{ money(max(0, $subtotal - $discount + $shipping)) }}</strong></div>
            <button class="sb-btn sb-btn-lg sb-btn-block mt-3"><i class="fas fa-check-circle"></i> Đặt hàng</button>
            <div class="sb-note-box mt-3">Phí ship: TP.HCM <b>{{ money($shippingTable['hcm']) }}</b> · lân cận <b>{{ money($shippingTable['near']) }}</b> · tỉnh khác <b>{{ money($shippingTable['other']) }}</b>. Miễn phí từ 300k.</div>
        </div>
    </aside>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var base = {{ $subtotal - $discount }}, table = @json($shippingTable);
    function fee(p) {
        p = (p || '').toLowerCase();
        if (p.indexOf('hồ chí minh') >= 0 || p.indexOf('hcm') >= 0 || p.indexOf('ho chi minh') >= 0) return table.hcm;
        if (/bình dương|đồng nai|long an|bà rịa|tây ninh/.test(p)) return table.near;
        return table.other;
    }
    function fmt(n) { return n.toLocaleString('vi-VN') + '₫'; }
    function refresh() {
        var c = document.querySelector('.addr-radio:checked'), isNew = !c || c.value === '';
        document.getElementById('newAddress').style.display = isNew ? '' : 'none';
        document.querySelectorAll('.sb-pay-option').forEach(function (l) { l.classList.toggle('checked', l.querySelector('input').checked); });
        var f = fee(isNew ? document.getElementById('provinceInput').value : c.dataset.province);
        document.getElementById('shipFee').textContent = f ? fmt(f) : 'Miễn phí';
        document.getElementById('grand').textContent = fmt(Math.max(0, base + f));
    }
    document.querySelectorAll('.sb-pay-option input').forEach(function (r) { r.addEventListener('change', refresh); });
    document.getElementById('provinceInput').addEventListener('input', refresh);
    refresh();
})();
</script>
@endpush
