@extends('layouts.shop')
@section('title', 'Sách yêu thích')

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-heart"></i> Tài khoản', 'title' => 'Sách yêu thích'])
<div class="sb-account">
    @include('shop.account._nav')
    <div>
        @if($books->count())
            <div class="sb-products cot-3">
                @foreach($books as $book)
                    <div>
                        @include('partials.book-card')
                        <form action="{{ route('wishlist.toggle', $book) }}" method="post" class="text-center mt-2">@csrf<button class="sb-btn sb-btn-outline sb-btn-sm"><i class="fas fa-heart-broken"></i> Bỏ yêu thích</button></form>
                    </div>
                @endforeach
            </div>
        @else
            <div class="sb-card sb-empty"><i class="far fa-heart"></i><h3>Chưa có sách yêu thích</h3><p>Bấm "Thêm vào yêu thích" ở trang sách để lưu lại.</p><a class="sb-btn" href="{{ route('books.index') }}">Khám phá sách</a></div>
        @endif
    </div>
</div>
@endsection
