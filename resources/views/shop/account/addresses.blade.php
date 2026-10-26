@extends('layouts.shop')
@section('title', 'Sổ địa chỉ')

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-map-marker-alt"></i> Tài khoản', 'title' => 'Sổ địa chỉ'])
<div class="sb-account">
    @include('shop.account._nav')
    <div class="sb-card sb-card-pad">
        <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-map-marker-alt"></i></span> Địa chỉ giao hàng</h3>
            <button class="sb-btn sb-btn-sm" data-bs-toggle="modal" data-bs-target="#addrNew"><i class="fas fa-plus"></i> Thêm địa chỉ</button></div>
        @forelse($addresses as $a)
            <div class="ms-addr">
                <div>
                    <b>{{ $a->receiver_name }}</b> · {{ $a->phone }} @if($a->is_default)<span class="sb-pill info">Mặc định</span>@endif
                    <div class="text-muted small">{{ $a->full }}</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if(!$a->is_default)<form method="post" action="{{ route('account.addresses.default', $a) }}">@csrf<button class="sb-btn sb-btn-soft sb-btn-sm">Đặt mặc định</button></form>@endif
                    <button class="sb-btn sb-btn-outline sb-btn-sm" data-bs-toggle="modal" data-bs-target="#addr{{ $a->id }}"><i class="fas fa-pen"></i> Sửa</button>
                    <form method="post" action="{{ route('account.addresses.delete', $a) }}" onsubmit="return confirm('Xóa địa chỉ này?')">@csrf @method('DELETE')<button class="sb-btn sb-btn-danger sb-btn-sm"><i class="fas fa-trash-alt"></i></button></form>
                </div>
            </div>
            @include('shop.account._address-modal', ['id' => 'addr' . $a->id, 'address' => $a, 'action' => route('account.addresses.update', $a), 'method' => 'PUT'])
        @empty
            <div class="sb-empty"><i class="fas fa-map-marked-alt"></i><h3>Chưa có địa chỉ</h3><p>Thêm địa chỉ để đặt hàng nhanh hơn.</p></div>
        @endforelse
    </div>
</div>
@include('shop.account._address-modal', ['id' => 'addrNew', 'address' => null, 'action' => route('account.addresses.store'), 'method' => 'POST'])
@endsection
