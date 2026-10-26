<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản trị') - Quản trị BenBooks</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/motsach.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/motsach-admin.css') }}">
    @stack('styles')
</head>
<body class="ad-body">
@php
    $u = auth()->user();
    $pendingOrders = \App\Models\Order::where('status', 'pending')->count();
    $pendingReturns = \App\Models\ReturnRequest::where('status', 'pending')->count();
    $nav = [
        ['Tổng quan', [
            ['admin.dashboard', 'admin.dashboard', 'fa-tachometer-alt', 'Bảng điều khiển', null, false],
        ]],
        ['Sản phẩm', [
            ['admin.books.index', 'admin.books.*', 'fa-book', 'Sách & truyện', null, false],
            ['admin.categories.index', 'admin.categories.*', 'fa-sitemap', 'Thể loại', null, false],
            ['admin.authors.index', 'admin.authors.*', 'fa-feather-alt', 'Tác giả', null, false],
            ['admin.publishers.index', 'admin.publishers.*', 'fa-building', 'Nhà xuất bản', null, false],
            ['admin.inventory.index', 'admin.inventory.*', 'fa-boxes', 'Tồn kho', null, false],
        ]],
        ['Bán hàng', [
            ['admin.orders.index', 'admin.orders.*', 'fa-receipt', 'Đơn hàng', $pendingOrders, false],
            ['admin.returns.index', 'admin.returns.*', 'fa-exchange-alt', 'Đổi / trả hàng', $pendingReturns, false],
            ['admin.reviews.index', 'admin.reviews.*', 'fa-star-half-alt', 'Đánh giá', null, false],
            ['admin.vouchers.index', 'admin.vouchers.*', 'fa-ticket-alt', 'Mã giảm giá', null, true],
            ['admin.flash-sales.index', 'admin.flash-sales.*', 'fa-bolt', 'Flash Sale', null, true],
            ['admin.reports.index', 'admin.reports.*', 'fa-chart-bar', 'Báo cáo', null, true],
            ['admin.users.index', 'admin.users.*', 'fa-users', 'Tài khoản & phân quyền', null, true],
        ]],
        ['AI & Phân tích', [
            ['admin.ai.behavior', 'admin.ai.behavior', 'fa-heartbeat', 'Hành vi khách hàng', null, true],
            ['admin.ai.segments', 'admin.ai.segments', 'fa-chart-pie', 'Phân cụm khách hàng', null, true],
            ['admin.ai.forecast', 'admin.ai.forecast', 'fa-chart-line', 'Dự báo', null, true],
            ['admin.ai.anomalies', 'admin.ai.anomalies', 'fa-shield-alt', 'Đơn bất thường', null, true],
            ['admin.ai.sentiment', 'admin.ai.sentiment', 'fa-smile', 'Cảm xúc đánh giá', null, true],
            ['admin.ai.graph', 'admin.ai.graph', 'fa-project-diagram', 'Knowledge Graph', null, true],
            ['admin.ai.search', 'admin.ai.search', 'fa-search-plus', 'Semantic Search', null, true],
            ['admin.ai.knowledge.index', 'admin.ai.knowledge.*', 'fa-book-reader', 'Kho tri thức (RAG)', null, true],
            ['admin.ai.chatbot', 'admin.ai.chatbot', 'fa-robot', 'Lịch sử chatbot', null, true],
            ['admin.ai.queue', 'admin.ai.queue', 'fa-layer-group', 'Hàng đợi (Queue)', null, true],
        ]],
    ];
    $initial = mb_strtoupper(mb_substr(collect(explode(' ', trim($u->name)))->last(), 0, 1));
@endphp
<div class="ad-layout">
    <aside class="ad-sidebar" id="adSidebar">
        <a class="ad-brand" href="{{ route('admin.dashboard') }}"><span class="ad-brand-icon"><i class="fas fa-book-open"></i></span><span>BenBooks<small>Quản trị</small></span></a>
        @foreach($nav as $group)
            @php $items = array_filter($group[1], function ($i) use ($u) { return !$i[5] || $u->isAdmin(); }); @endphp
            @if(count($items))
                <div class="ad-nav-label">{{ $group[0] }}</div>
                <nav class="ad-nav">
                    @foreach($items as $i)
                        <a href="{{ route($i[0]) }}" class="{{ request()->routeIs($i[1]) ? 'active' : '' }}"><i class="fas {{ $i[2] }}"></i> {{ $i[3] }}@if($i[4])<span class="ad-count">{{ $i[4] }}</span>@endif</a>
                    @endforeach
                </nav>
            @endif
        @endforeach
        <div class="ad-sidebar-foot">
            @if($u->isAdmin())<a href="{{ route('shipper.index') }}"><i class="fas fa-mobile-alt"></i> App giao hàng</a>@endif
            <a href="{{ route('home') }}" target="_blank"><i class="fas fa-store"></i> Xem cửa hàng</a>
            <a href="#" data-bs-toggle="modal" data-bs-target="#modalDangXuat"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </aside>

    <main class="ad-main">
        <div class="ad-topbar">
            <button class="sb-icon-btn ad-menu-btn" type="button" onclick="document.getElementById('adSidebar').classList.toggle('mo')" aria-label="Menu"><i class="fas fa-bars"></i></button>
            <form action="{{ route('admin.orders.index') }}" method="get" role="search"><i class="fas fa-search"></i><input name="q" placeholder="Tìm mã đơn, tên khách, số điện thoại..." aria-label="Tìm đơn hàng"></form>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a class="sb-icon-btn ad-bell" id="notifBell" href="{{ route('notifications.index') }}" data-url="{{ route('notifications.poll') }}" title="Thông báo realtime"><i class="fas fa-bell"></i><span class="sb-badge-count do" style="display:none">0</span></a>
                <div class="dropdown">
                    <a href="#" class="ad-user" data-bs-toggle="dropdown"><span class="av">{{ $initial }}</span><span>{{ $u->name }}<small>{{ $u->role_name }}</small></span></a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="{{ route('account.profile') }}"><i class="fas fa-id-card"></i> Tài khoản</a>
                        <a class="dropdown-item" href="{{ route('home') }}"><i class="fas fa-store"></i> Cửa hàng</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#modalDangXuat"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="ad-head">
            <div><span class="sb-eyebrow">@yield('eyebrow', 'Quản trị')</span><h1>@yield('title', 'Quản trị')</h1></div>
            <div class="d-flex gap-2 flex-wrap">@yield('actions')</div>
        </div>
        @include('partials.flash')
        @yield('content')
    </main>
</div>

<div class="modal fade sb-modal" id="modalDangXuat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content center">
        <div class="sb-modal-icon"><i class="fas fa-sign-out-alt"></i></div>
        <h3>Đăng xuất?</h3><p>Bạn sẽ thoát khỏi trang quản trị.</p>
        <form method="post" action="{{ route('logout') }}" class="sb-modal-actions">@csrf
            <button type="button" class="sb-btn sb-btn-outline" data-bs-dismiss="modal">Ở lại</button><button class="sb-btn sb-btn-danger">Đăng xuất</button></form>
    </div></div>
</div>
<div class="sb-toasts"></div>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/chart.umd.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
<script>
    if (window.Chart) {
        Chart.defaults.font.family = '"Open Sans", "Segoe UI", Arial, sans-serif';
        Chart.defaults.color = '#66748A';
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
    }
    window.MS_COLORS = ['#2F6BFF', '#FF6A1A', '#12A15E', '#7B5CFF', '#FFB800', '#E5484D', '#0B1B33', '#5B8CFF'];
</script>
@stack('scripts')
</body>
</html>
