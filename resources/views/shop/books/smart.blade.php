@extends('layouts.shop')
@section('title', 'Tìm sách bằng AI')

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-magic"></i> Semantic Search', 'title' => 'Tìm sách bằng câu nói tự nhiên',
    'desc' => 'Hệ thống hiểu thể loại, tác giả, khoảng giá, năm xuất bản, cách sắp xếp và tìm theo ý nghĩa (vector TF-IDF + từ đồng nghĩa) chứ không chỉ khớp chữ.'])

<div class="sb-card ms-ai-box">
    <form>
        <input name="q" value="{{ $q }}" placeholder="VD: sách giúp quản lý tiền bạc cho người trẻ dưới 150k" autofocus aria-label="Câu tìm kiếm">
        <button class="sb-btn sb-btn-lg"><i class="fas fa-search"></i> Tìm</button>
    </form>
    <div class="sb-coupon-list justify-content-center mt-3">
        @foreach($examples as $ex)<a href="{{ route('books.smart', ['q' => $ex]) }}" class="sb-chip" style="font-size:.78rem">{{ $ex }}</a>@endforeach
    </div>
</div>

@if($q !== '')
    <div class="ms-understood">
        <b class="me-1"><i class="fas fa-brain" style="color: var(--sb-primary)"></i> Hệ thống hiểu:</b>
        @forelse($understood as $u)<span class="sb-pill info">{{ $u }}</span>@empty<span class="text-muted">không có điều kiện đặc biệt</span>@endforelse
    </div>
    @if($books->count())
        <div class="sb-products">
            @foreach($books as $book)
                <div>
                    @include('partials.book-card')
                    @if($book->score > 0)<div class="text-center sb-score mt-2"><i class="fas fa-bullseye"></i> Độ liên quan {{ number_format($book->score * 100, 1) }}%</div>@endif
                </div>
            @endforeach
        </div>
    @else
        <div class="sb-card sb-empty"><i class="fas fa-search"></i><h3>Chưa tìm thấy sách phù hợp</h3><p>Bạn thử diễn đạt khác hoặc bớt điều kiện nhé.</p></div>
    @endif
@endif
@endsection
