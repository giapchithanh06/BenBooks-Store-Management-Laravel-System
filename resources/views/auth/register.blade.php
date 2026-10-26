@extends('layouts.shop')
@section('title', 'Đăng ký')

@section('content')
<div style="height:50px"></div>
<div class="sb-card sb-auth">
    <div class="sb-auth-side" style="background: var(--sb-dark-bg)">
        <span class="sb-eyebrow" style="color:#FFB27D"><i class="fas fa-gift"></i> Thành viên mới</span>
        <h2>Tạo tài khoản BenBooks</h2>
        <p>Nhập mã <b>MOTSACH10</b> để được giảm 10% cho đơn hàng đầu tiên. Miễn phí giao hàng cho đơn từ 300.000₫.</p>
    </div>
    <form method="post" action="{{ route('register.post') }}" class="sb-auth-form">
        @csrf
        <span class="sb-eyebrow">Tài khoản</span>
        <h1>Đăng ký</h1>
        <p class="text-muted mb-4">Đã có tài khoản? <a href="{{ route('login') }}" class="fw-bold">Đăng nhập</a></p>
        <div class="sb-field"><label>Họ tên <span class="req">*</span></label><div class="sb-input-icon"><i class="fas fa-user"></i><input class="sb-control" name="name" value="{{ old('name') }}" required></div>@error('name')<span class="sb-error">{{ $message }}</span>@enderror</div>
        <div class="sb-field"><label>Email <span class="req">*</span></label><div class="sb-input-icon"><i class="fas fa-envelope"></i><input class="sb-control" type="email" name="email" value="{{ old('email') }}" required></div>@error('email')<span class="sb-error">{{ $message }}</span>@enderror</div>
        <div class="sb-field"><label>Số điện thoại <span class="req">*</span></label><div class="sb-input-icon"><i class="fas fa-phone"></i><input class="sb-control" name="phone" value="{{ old('phone') }}" required pattern="0[0-9]{9}"></div>@error('phone')<span class="sb-error">{{ $message }}</span>@enderror</div>
        <div class="row">
            <div class="col-md-6 sb-field"><label>Mật khẩu <span class="req">*</span></label><input class="sb-control" type="password" name="password" required minlength="6">@error('password')<span class="sb-error">{{ $message }}</span>@enderror</div>
            <div class="col-md-6 sb-field"><label>Nhập lại <span class="req">*</span></label><input class="sb-control" type="password" name="password_confirmation" required></div>
        </div>
        <button class="sb-btn sb-btn-lg sb-btn-block"><i class="fas fa-user-plus"></i> Tạo tài khoản</button>
    </form>
</div>
@endsection
