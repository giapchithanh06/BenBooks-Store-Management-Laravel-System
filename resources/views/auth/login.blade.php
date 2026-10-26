@extends('layouts.shop')
@section('title', 'Đăng nhập')

@section('content')
<div style="height:50px"></div>
<div class="sb-card sb-auth">
    <div class="sb-auth-side" style="background: var(--sb-dark-bg)">
        <span class="sb-eyebrow" style="color:#FFB27D"><i class="fas fa-book-reader"></i> Chào BenBooks</span>
        <h2>Đăng nhập để mua sách nhanh hơn</h2>
        <p>Theo dõi đơn hàng, lưu sách yêu thích, nhận gợi ý sách theo sở thích và ưu đãi dành riêng cho bạn.</p>
        <div class="sb-note-box mt-4" style="background: rgba(255,255,255,.08); color: rgba(255,255,255,.85)">
            <b style="color:#fff">Tài khoản demo</b> (mật khẩu <b style="color:#fff">123456</b>)
            <ul><li>admin@motsach.vn – Quản trị</li><li>staff@motsach.vn – Nhân viên</li><li>shipper@motsach.vn – Giao hàng</li><li>khach@motsach.vn – Khách hàng</li></ul>
        </div>
    </div>
    <form method="post" action="{{ route('login.post') }}" class="sb-auth-form">
        @csrf
        <span class="sb-eyebrow">Tài khoản</span>
        <h1>Đăng nhập</h1>
        <p class="text-muted mb-4">Chưa có tài khoản? <a href="{{ route('register') }}" class="fw-bold">Đăng ký ngay</a></p>
        <div class="sb-field"><label>Email</label><div class="sb-input-icon"><i class="fas fa-envelope"></i><input class="sb-control" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="email@vidu.com"></div>
            @error('email')<span class="sb-error">{{ $message }}</span>@enderror</div>
        <div class="sb-field"><label>Mật khẩu</label><div class="sb-input-icon position-relative"><i class="fas fa-lock"></i><input class="sb-control" type="password" name="password" id="pw" required placeholder="••••••">
            <button type="button" class="sb-pass-toggle" onclick="var p=document.getElementById('pw');p.type=p.type==='password'?'text':'password'" aria-label="Hiện mật khẩu"><i class="far fa-eye"></i></button></div></div>
        <label class="sb-check mb-4"><input type="checkbox" name="remember" value="1"> Ghi nhớ đăng nhập</label>
        <button class="sb-btn sb-btn-lg sb-btn-block"><i class="fas fa-sign-in-alt"></i> Đăng nhập</button>
    </form>
</div>
@endsection
