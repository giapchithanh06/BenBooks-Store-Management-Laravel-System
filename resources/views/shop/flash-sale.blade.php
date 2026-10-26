@extends('layouts.shop')
@section('title', 'Flash Sale')

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-bolt"></i> Giá sốc giới hạn', 'title' => 'Flash Sale', 'desc' => 'Giảm sâu trong thời gian ngắn, số suất có hạn – giá tự áp dụng khi thêm vào giỏ.'])
@forelse($sales as $sale)
    @php $running = $sale->status_name === 'Đang diễn ra'; @endphp
    <section class="sb-card sb-card-pad mb-4">
        <div class="sb-box-head">
            <h3><span class="sb-box-icon me-2"><i class="fas fa-bolt"></i></span> {{ $sale->name }}</h3>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="sb-status {{ $running ? 'xong' : 'cho' }}">{{ $sale->status_name }}</span>
                <span class="small text-muted">{{ $sale->starts_at->format('H:i d/m') }} → {{ $sale->ends_at->format('H:i d/m/Y') }}</span>
                @if($running)<span class="ms-countdown" data-countdown="{{ $sale->ends_at->toIso8601String() }}"></span>
                @elseif($sale->status_name === 'Sắp diễn ra')<span class="small">Bắt đầu sau</span><span class="ms-countdown" data-countdown="{{ $sale->starts_at->toIso8601String() }}"></span>@endif
            </div>
        </div>
        <div class="sb-products">
            @foreach($sale->items as $item)
                @continue(!$item->variant || !$item->variant->book)
                @php $fb = $item->variant->book; @endphp
                <div class="sb-product">
                    <a class="sb-product-img" href="{{ route('books.show', $fb->slug) }}">
                        <span class="sb-tag giam"><i class="fas fa-bolt"></i> Flash</span>
                        <span class="sb-percent">-{{ (int) round(100 - $item->sale_price * 100 / max(1, $item->variant->price)) }}%</span>
                        <img src="{{ $fb->cover_url }}" alt="{{ $fb->title }}" loading="lazy">
                    </a>
                    <div class="sb-product-body">
                        <span class="sb-product-hang">{{ $item->variant->name }} · {{ $fb->author_names }}</span>
                        <h3 class="sb-product-name"><a href="{{ route('books.show', $fb->slug) }}">{{ $fb->title }}</a></h3>
                        <div class="sb-product-price"><span class="sb-price">{{ money($item->sale_price) }}</span><span class="sb-old-price">{{ money($item->variant->price) }}</span></div>
                        <div class="ms-progress"><i style="width: {{ max(6, $item->sold_percent) }}%"></i><small>Đã bán {{ $item->sold }}/{{ $item->quantity }}</small></div>
                        <div class="sb-product-actions">
                            @if($running && $item->remaining > 0 && $item->variant->stock > 0)
                                <form action="{{ route('cart.add') }}" method="post" data-ajax="cart" data-cart-url="{{ route('cart.index') }}">@csrf<input type="hidden" name="variant_id" value="{{ $item->variant_id }}"><button class="sb-btn sb-btn-sm"><i class="fas fa-cart-plus"></i> Mua ngay</button></form>
                            @else
                                <span class="sb-btn sb-btn-sm disabled">{{ $running ? 'Hết suất' : 'Chưa mở bán' }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@empty
    <div class="sb-card sb-empty"><i class="fas fa-hourglass-half"></i><h3>Chưa có chương trình Flash Sale</h3><p>Quay lại sau nhé!</p></div>
@endforelse
@endsection
