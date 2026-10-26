<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B1B33">
    <title>@yield('title', 'App giao hàng') - BenBooks Express</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/motsach.css') }}">
</head>
<body style="background: var(--sb-gray); padding-bottom: 80px">
<div class="ms-ship-bar">
    <img src="{{ asset('assets/img/logo-motsach.svg') }}" alt="BenBooks">
    <div class="ms-auto text-end small"><b>{{ auth()->user()->name }}</b><br><span class="text-white-50">Nhân viên giao hàng</span></div>
    <a class="sb-icon-btn" id="notifBell" href="{{ route('notifications.index') }}" data-url="{{ route('notifications.poll') }}" aria-label="Thông báo"><i class="fas fa-bell"></i><span class="sb-badge-count do" style="display:none">0</span></a>
</div>
<div class="container py-3" style="max-width:640px">
    @include('partials.flash')
    @yield('content')
</div>
<nav class="ms-bottom-nav">
    <a href="{{ route('shipper.index') }}" class="{{ request()->routeIs('shipper.index') && request('tab', 'todo') === 'todo' ? 'active' : '' }}"><i class="fas fa-box"></i>Cần giao</a>
    <a href="{{ route('shipper.index', ['tab' => 'done']) }}" class="{{ request('tab') === 'done' ? 'active' : '' }}"><i class="fas fa-check-double"></i>Đã xong</a>
    <a href="{{ route('home') }}"><i class="fas fa-store"></i>Cửa hàng</a>
    <a href="#" onclick="event.preventDefault(); document.getElementById('logoutForm').submit()"><i class="fas fa-sign-out-alt"></i>Đăng xuất</a>
</nav>
<form id="logoutForm" method="post" action="{{ route('logout') }}" class="d-none">@csrf</form>
<div class="sb-toasts"></div>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
