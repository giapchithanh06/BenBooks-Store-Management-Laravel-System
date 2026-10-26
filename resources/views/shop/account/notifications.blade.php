@extends($layout)
@section('title', 'Thông báo')

@section('content')
@if($layout === 'layouts.shop')
    @include('partials.page-head', ['eyebrow' => '<i class="fas fa-bell"></i> Tài khoản', 'title' => 'Thông báo'])
@endif
<div class="{{ $layout === 'layouts.shop' ? 'sb-account' : '' }}">
    @if($layout === 'layouts.shop')@include('shop.account._nav')@endif
    <div class="{{ $layout === 'layouts.shop' ? 'sb-card sb-card-pad' : 'ad-panel' }}">
        <div class="sb-box-head"><h3><span class="sb-box-icon me-2"><i class="fas fa-bell"></i></span> Tất cả thông báo</h3>
            <form method="post" action="{{ route('notifications.readAll') }}">@csrf<button class="sb-btn sb-btn-soft sb-btn-sm"><i class="fas fa-check-double"></i> Đánh dấu đã đọc</button></form></div>
        @forelse($notifications as $n)
            <a href="{{ route('notifications.open', $n) }}" class="ms-notif {{ $n->is_read ? '' : 'moi' }}">
                <span class="ico"><i class="fas fa-bell"></i></span>
                <span><b>{{ $n->title }}</b>{{ $n->content }}<br><small>{{ $n->created_at->diffForHumans() }}</small></span>
            </a>
        @empty
            <div class="sb-empty"><i class="far fa-bell"></i><h3>Chưa có thông báo</h3></div>
        @endforelse
        {{ $notifications->links('partials.pagination') }}
    </div>
</div>
@endsection
