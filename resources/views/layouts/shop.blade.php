<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="BenBooks - nhà sách & truyện tranh online: văn học, kinh tế, kỹ năng, thiếu nhi, manga, manhwa, comic.">
    <meta name="author" content="Giáp Chí Thanh">
    <title>@yield('title', 'Trang chủ') - BenBooks</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/motsach.css') }}">
    @stack('styles')
</head>
<body>
@php
    $menu = [
        ['home', route('home'), 'Trang chủ'],
        ['books.index', route('books.index'), 'Sách'],
        ['comics', route('books.category', 'truyen-tranh'), 'Truyện tranh'],
        ['flash-sale', route('flash-sale'), 'Flash Sale'],
        ['books.smart', route('books.smart'), 'Tìm bằng AI'],
        ['policies', route('policies'), 'Chính sách'],
    ];
    $current = request()->route() ? request()->route()->getName() : '';
    $isComic = request()->is('the-loai/truyen-tranh*') || request()->is('the-loai/manga') || request()->is('the-loai/manhwa') || request()->is('the-loai/manhua') || request()->is('the-loai/comic');
@endphp
<header class="sb-header">
    <nav class="navbar navbar-expand-lg container">
        <a class="navbar-brand sb-logo" href="{{ route('home') }}" aria-label="BenBooks - Trang chủ"><img src="{{ asset('assets/img/logo-motsach.svg') }}" alt="BenBooks"></a>
        <div class="d-flex align-items-center d-lg-none" style="gap: 8px;">
            <a class="sb-icon-btn" href="{{ route('cart.index') }}" aria-label="Giỏ hàng"><i class="fas fa-shopping-cart"></i><span class="sb-badge-count" data-so-gio>{{ $cartCount ?? 0 }}</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuChinh" aria-controls="menuChinh" aria-expanded="false" aria-label="Mở menu"><i class="fas fa-bars"></i></button>
        </div>
        <div class="collapse navbar-collapse" id="menuChinh">
            <ul class="navbar-nav mx-auto">
                @foreach($menu as $m)
                    @php $active = $m[0] === 'comics' ? $isComic : ($current === $m[0] && !$isComic); @endphp
                    <li class="nav-item {{ $active ? 'active' : '' }}"><a class="nav-link" href="{{ $m[1] }}">{{ $m[2] }}</a></li>
                @endforeach
            </ul>
            <div class="d-flex align-items-center sb-header-actions" style="gap: 10px;">
                <form class="sb-search" action="{{ route('books.search') }}" method="get" role="search">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm sách, tác giả, ISBN..." aria-label="Tìm sách" autocomplete="off" data-suggest="{{ route('books.suggest') }}">
                    <button type="submit" aria-label="Tìm"><i class="fas fa-search"></i></button>
                    <div class="ms-suggest"></div>
                </form>
                @auth
                    <a class="sb-icon-btn d-none d-lg-inline-flex" id="notifBell" href="{{ route('notifications.index') }}" data-url="{{ route('notifications.poll') }}" title="Thông báo" aria-label="Thông báo"><i class="fas fa-bell"></i><span class="sb-badge-count do" style="display:none">0</span></a>
                    <a class="sb-icon-btn d-none d-lg-inline-flex" href="{{ route('account.wishlist') }}" title="Yêu thích" aria-label="Sách yêu thích"><i class="fas fa-heart"></i></a>
                @endauth
                <a class="sb-icon-btn d-none d-lg-inline-flex" href="{{ route('cart.index') }}" title="Giỏ hàng" aria-label="Giỏ hàng"><i class="fas fa-shopping-cart"></i><span class="sb-badge-count" data-so-gio>{{ $cartCount ?? 0 }}</span></a>
                @auth
                    @php $u = auth()->user(); @endphp
                    <div class="dropdown">
                        <button class="sb-btn sb-btn-grad sb-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <span class="sb-user-name">{{ $u->name }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{ route('account.profile') }}"><i class="fas fa-id-card"></i> Tài khoản của tôi</a>
                            <a class="dropdown-item" href="{{ route('orders.index') }}"><i class="fas fa-receipt"></i> Đơn hàng của tôi</a>
                            <a class="dropdown-item" href="{{ route('account.wishlist') }}"><i class="fas fa-heart"></i> Sách yêu thích</a>
                            <a class="dropdown-item" href="{{ route('notifications.index') }}"><i class="fas fa-bell"></i> Thông báo</a>
                            @if($u->isBackend())
                                <a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="fas fa-shield-alt"></i> Trang quản trị</a>
                            @endif
                            @if($u->isShipper())
                                <a class="dropdown-item" href="{{ route('shipper.index') }}"><i class="fas fa-shipping-fast"></i> App giao hàng</a>
                            @endif
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#modalDangXuat"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                        </div>
                    </div>
                @else
                    <a class="sb-btn sb-btn-grad" href="{{ route('login') }}"><i class="fas fa-user"></i> Đăng nhập</a>
                @endauth
            </div>
        </div>
    </nav>
</header>

<main class="sb-main">
    @hasSection('banner')
        <section class="sb-top-banner">
            <span class="sb-eyebrow">@yield('banner')</span>
            <h1>@yield('banner_title')</h1>
        </section>
    @endif
    <div class="container">
        @include('partials.flash')
        @yield('content')
    </div>
</main>

@php $footerPublishers = \App\Models\Publisher::orderBy('name')->take(10)->get(); @endphp
<section class="sb-logos" aria-label="Nhà xuất bản">
    <div class="container">
        <span class="sb-logos-title">Nhà xuất bản & đối tác phát hành</span>
        @foreach($footerPublishers as $p)
            <a href="{{ route('books.publisher', $p->slug) }}" class="fw-bold" style="font-family: var(--sb-display); color: var(--sb-navy)"><i class="fas fa-book-open me-2" style="color: var(--sb-primary)"></i>{{ $p->name }}</a>
        @endforeach
    </div>
</section>
<footer class="sb-footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <img class="sb-footer-logo" src="{{ asset('assets/img/logo-motsach.svg') }}" alt="BenBooks">
                <p style="max-width: 36ch;">Nhà sách & truyện tranh trực tuyến: sách chính hãng, giao nhanh toàn quốc, đổi trả 7 ngày, miễn phí ship đơn từ 300.000₫.</p>
            </div>
            <div class="col-lg-2 col-md-6 col-6 mb-4">
                <h4>Mua sắm</h4>
                <ul>
                    @foreach(($menuCategories ?? collect())->take(2) as $root)
                        <li><a href="{{ route('books.category', $root->slug) }}">{{ $root->name }}</a></li>
                    @endforeach
                    <li><a href="{{ route('flash-sale') }}">Flash Sale</a></li>
                    <li><a href="{{ route('vouchers') }}">Mã giảm giá</a></li>
                    <li><a href="{{ route('books.smart') }}">Tìm sách bằng AI</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 col-6 mb-4">
                <h4>Hỗ trợ</h4>
                <ul>
                    <li><a href="{{ route('orders.track') }}">Tra cứu đơn hàng</a></li>
                    <li><a href="{{ route('policies') }}#doi-tra">Đổi trả</a></li>
                    <li><a href="{{ route('policies') }}#giao-hang">Vận chuyển</a></li>
                    <li><a href="{{ route('policies') }}#thanh-toan">Thanh toán</a></li>
                </ul>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <h4>Liên hệ</h4>
                <ul class="sb-contact">
                    <li><i class="fas fa-map-marker-alt"></i> 140 Lê Trọng Tấn, Phường Tây Thạnh, TP. Hồ Chí Minh</li>
                    <li><i class="fas fa-phone"></i> 1900 6868 (8:00 – 21:00)</li>
                    <li><i class="fas fa-envelope"></i> hotro@motsach.vn</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="sb-footer-bottom">
        <div class="container">Bản quyền &copy; {{ date('Y') }} BenBooks · Thiết kế &amp; phát triển: <strong>Giáp Chí Thanh</strong> · <a href="{{ route('policies') }}#doi-tra">Chính sách đổi trả</a> · Ảnh bìa trong dữ liệu mẫu là ảnh minh họa.</div>
    </div>
</footer>

@auth
<div class="modal fade sb-modal" id="modalDangXuat" tabindex="-1" aria-labelledby="tieuDeDangXuat" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content center">
        <div class="sb-modal-icon"><i class="fas fa-sign-out-alt"></i></div>
        <h3 id="tieuDeDangXuat">Đăng xuất?</h3>
        <p>Bạn có chắc muốn đăng xuất khỏi BenBooks không?</p>
        <form method="post" action="{{ route('logout') }}" class="sb-modal-actions">
            @csrf
            <button type="button" class="sb-btn sb-btn-outline" data-bs-dismiss="modal">Ở lại</button>
            <button class="sb-btn sb-btn-danger">Đăng xuất</button>
        </form>
    </div></div>
</div>
@endauth

{{-- Chatbot tư vấn + RAG --}}
<button class="ms-chat-fab" id="chatFab" type="button" title="Trợ lý BenBooks" aria-label="Mở trợ lý"><i class="fas fa-comment-dots"></i></button>
<div class="ms-chat" id="chatBox" data-url="{{ route('chatbot.ask') }}">
    <div class="ms-chat-head">
        <span class="av"><i class="fas fa-robot"></i></span>
        <div><b>Trợ lý BenBooks</b><small>Tư vấn sách · Chính sách · Đơn hàng</small></div>
        <button type="button" data-close aria-label="Đóng"><i class="fas fa-times"></i></button>
    </div>
    <div class="ms-chat-body">
        <div class="ms-msg bot">Xin chào! Mình giúp bạn tìm sách theo sở thích, kiểm tra đơn hàng và giải đáp chính sách đổi trả, giao hàng. 📚</div>
        <div class="ms-chat-quick">
            <button type="button" data-q="Gợi ý manga hành động dưới 50k">Manga dưới 50k</button>
            <button type="button" data-q="Sách giúp làm giàu cho người trẻ">Sách làm giàu</button>
            <button type="button" data-q="Chính sách đổi trả như thế nào?">Đổi trả</button>
            <button type="button" data-q="Có mã giảm giá nào không?">Mã giảm giá</button>
            <button type="button" data-q="Đơn hàng của tôi đang ở đâu?">Đơn của tôi</button>
        </div>
    </div>
    <form><input placeholder="Nhập câu hỏi..." maxlength="500" aria-label="Câu hỏi"><button aria-label="Gửi"><i class="fas fa-paper-plane"></i></button></form>
</div>
<div class="sb-toasts"></div>

<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
