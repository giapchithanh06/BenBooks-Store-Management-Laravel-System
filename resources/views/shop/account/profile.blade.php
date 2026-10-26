@extends('layouts.shop')
@section('title', 'Tài khoản của tôi')

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-user"></i> Tài khoản', 'title' => 'Xin chào, ' . $user->name])
<div class="sb-account">
    @include('shop.account._nav')
    <div>
        <div class="ms-stat-row">
            <div class="sb-stat"><strong>{{ $stats['orders'] }}</strong><span>Đơn hàng</span></div>
            <div class="sb-stat"><strong style="font-size:1.3rem">{{ money($stats['spent']) }}</strong><span>Đã chi tiêu</span></div>
            <div class="sb-stat"><strong>{{ $stats['wishlist'] }}</strong><span>Yêu thích</span></div>
            <div class="sb-stat"><strong>{{ $stats['reviews'] }}</strong><span>Đánh giá</span></div>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <form method="post" action="{{ route('account.profile.update') }}" class="sb-card sb-card-pad h-100">
                    @csrf
                    <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-id-card"></i></span> Thông tin cá nhân</h3></div>
                    <div class="sb-field"><label>Họ tên</label><div class="sb-input-icon"><i class="fas fa-user"></i><input class="sb-control" name="name" value="{{ old('name', $user->name) }}" required></div></div>
                    <div class="sb-field"><label>Email</label><div class="sb-input-icon"><i class="fas fa-envelope"></i><input class="sb-control" type="email" name="email" value="{{ old('email', $user->email) }}" required></div></div>
                    <div class="sb-field"><label>Số điện thoại</label><div class="sb-input-icon"><i class="fas fa-phone"></i><input class="sb-control" name="phone" value="{{ old('phone', $user->phone) }}"></div></div>
                    <button class="sb-btn"><i class="fas fa-save"></i> Lưu thay đổi</button>
                </form>
            </div>
            <div class="col-lg-6">
                <form method="post" action="{{ route('account.password') }}" class="sb-card sb-card-pad h-100">
                    @csrf
                    <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-key"></i></span> Đổi mật khẩu</h3></div>
                    <div class="sb-field"><label>Mật khẩu hiện tại</label><div class="sb-input-icon"><i class="fas fa-lock"></i><input class="sb-control" type="password" name="current_password" required></div></div>
                    <div class="sb-field"><label>Mật khẩu mới</label><div class="sb-input-icon"><i class="fas fa-lock"></i><input class="sb-control" type="password" name="password" minlength="6" required></div></div>
                    <div class="sb-field"><label>Nhập lại mật khẩu mới</label><div class="sb-input-icon"><i class="fas fa-lock"></i><input class="sb-control" type="password" name="password_confirmation" required></div></div>
                    <button class="sb-btn sb-btn-outline"><i class="fas fa-key"></i> Đổi mật khẩu</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
