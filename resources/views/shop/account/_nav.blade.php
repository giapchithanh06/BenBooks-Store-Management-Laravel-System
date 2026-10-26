@php $me = auth()->user(); @endphp
<aside class="sb-card sb-account-nav">
    <div class="sb-account-user">
        <div class="sb-avatar">{{ mb_strtoupper(mb_substr(collect(explode(' ', trim($me->name)))->last(), 0, 1)) }}</div>
        <b style="font-family: var(--sb-display)">{{ $me->name }}</b>
        <div class="small text-muted">{{ $me->email }}</div>
        @if($me->segment && $me->segment !== 'Chưa mua hàng')<span class="sb-pill warn mt-2"><i class="fas fa-crown"></i> {{ $me->segment }}</span>@endif
    </div>
    <a href="{{ route('account.profile') }}" class="{{ request()->routeIs('account.profile') ? 'active' : '' }}"><i class="fas fa-id-card"></i> Thông tin tài khoản</a>
    <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}"><i class="fas fa-receipt"></i> Đơn hàng của tôi</a>
    <a href="{{ route('account.addresses') }}" class="{{ request()->routeIs('account.addresses') ? 'active' : '' }}"><i class="fas fa-map-marker-alt"></i> Sổ địa chỉ</a>
    <a href="{{ route('account.wishlist') }}" class="{{ request()->routeIs('account.wishlist') ? 'active' : '' }}"><i class="fas fa-heart"></i> Sách yêu thích</a>
    <a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.index') ? 'active' : '' }}"><i class="fas fa-bell"></i> Thông báo</a>
    <a href="#" data-bs-toggle="modal" data-bs-target="#modalDangXuat"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
</aside>
