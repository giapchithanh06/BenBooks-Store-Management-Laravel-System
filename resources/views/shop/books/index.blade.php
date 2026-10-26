@extends('layouts.shop')
@section('title', $title)

@section('content')
@php
    $eyebrow = '<i class="fas fa-book"></i> Cửa hàng';
    $desc = 'Lọc theo thể loại, tác giả, nhà xuất bản, năm xuất bản, ISBN và khoảng giá.';
    if ($heading instanceof \App\Models\Author) { $eyebrow = '<i class="fas fa-feather-alt"></i> Tác giả'; $desc = $heading->bio ?: 'Các tác phẩm của ' . $heading->name . ' đang có tại BenBooks.'; }
    elseif ($heading instanceof \App\Models\Publisher) { $eyebrow = '<i class="fas fa-building"></i> Nhà xuất bản'; $desc = $heading->address; }
    elseif ($heading instanceof \App\Models\Category) { $eyebrow = '<i class="fas fa-layer-group"></i> Thể loại'; $desc = $heading->description; }
    $sorts = ['' => 'Mới nhất', 'best' => 'Bán chạy', 'price_asc' => 'Giá tăng dần', 'price_desc' => 'Giá giảm dần', 'rating' => 'Đánh giá cao', 'year' => 'Năm XB mới', 'view' => 'Xem nhiều'];
    $labels = ['q' => 'Từ khóa', 'isbn' => 'ISBN', 'year_from' => 'Từ năm', 'year_to' => 'Đến năm', 'price_min' => 'Giá từ', 'price_max' => 'Giá đến', 'format' => 'Phiên bản', 'rating' => 'Sao ≥', 'in_stock' => 'Còn hàng'];
@endphp
@include('partials.page-head', ['eyebrow' => $eyebrow, 'title' => $heading ? $heading->name : $title, 'desc' => $desc])

<div class="sb-shop">
    {{-- Bộ lọc (tìm kiếm nâng cao) --}}
    <form method="get" action="{{ url()->current() }}" class="sb-card sb-filter">
        <div class="sb-filter-head"><span><i class="fas fa-sliders-h me-2"></i> Bộ lọc</span>
            <span><a href="{{ url()->current() }}">Xóa lọc</a><button type="button" class="sb-filter-toggle" aria-label="Mở bộ lọc"><i class="fas fa-chevron-down"></i></button></span></div>
        <div class="sb-filter-group">
            <label class="sb-label"><i class="fas fa-search"></i> Từ khóa</label>
            <input class="sb-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên sách, tác giả, NXB">
        </div>
        <div class="sb-filter-group">
            <label class="sb-label"><i class="fas fa-barcode"></i> Mã ISBN</label>
            <input class="sb-control" name="isbn" value="{{ $filters['isbn'] ?? '' }}" placeholder="978604...">
        </div>
        @if(!($heading instanceof \App\Models\Category))
        <div class="sb-filter-group">
            <label class="sb-label"><i class="fas fa-layer-group"></i> Thể loại</label>
            <select class="sb-control" name="category">
                <option value="">Tất cả thể loại</option>
                @foreach($categories as $c)
                    <option value="{{ $c->slug }}" @if(($filters['category'] ?? '') == $c->slug) selected @endif>{{ $c->name }}</option>
                    @foreach($c->children as $ch)<option value="{{ $ch->slug }}" @if(($filters['category'] ?? '') == $ch->slug) selected @endif>— {{ $ch->name }}</option>@endforeach
                @endforeach
            </select>
        </div>
        @endif
        @if(!($heading instanceof \App\Models\Author))
        <div class="sb-filter-group">
            <label class="sb-label"><i class="fas fa-feather-alt"></i> Tác giả</label>
            <select class="sb-control" name="author">
                <option value="">Tất cả tác giả</option>
                @foreach($authors as $a)<option value="{{ $a->id }}" @if(($filters['author'] ?? '') == $a->id) selected @endif>{{ $a->name }}</option>@endforeach
            </select>
        </div>
        @endif
        @if(!($heading instanceof \App\Models\Publisher))
        <div class="sb-filter-group">
            <label class="sb-label"><i class="fas fa-building"></i> Nhà xuất bản</label>
            <select class="sb-control" name="publisher">
                <option value="">Tất cả NXB</option>
                @foreach($publishers as $p)<option value="{{ $p->id }}" @if(($filters['publisher'] ?? '') == $p->id) selected @endif>{{ $p->name }}</option>@endforeach
            </select>
        </div>
        @endif
        <div class="sb-filter-group">
            <label class="sb-label"><i class="fas fa-calendar-alt"></i> Năm xuất bản</label>
            <div class="ms-two"><input class="sb-control" type="number" name="year_from" value="{{ $filters['year_from'] ?? '' }}" placeholder="Từ"><input class="sb-control" type="number" name="year_to" value="{{ $filters['year_to'] ?? '' }}" placeholder="Đến"></div>
        </div>
        <div class="sb-filter-group">
            <label class="sb-label"><i class="fas fa-tags"></i> Khoảng giá (₫)</label>
            <div class="ms-two"><input class="sb-control" type="number" step="1000" name="price_min" value="{{ $filters['price_min'] ?? '' }}" placeholder="Từ"><input class="sb-control" type="number" step="1000" name="price_max" value="{{ $filters['price_max'] ?? '' }}" placeholder="Đến"></div>
            <div class="sb-chip-list mt-2">
                @foreach([[0, 50000, 'Dưới 50k'], [50000, 100000, '50–100k'], [100000, 200000, '100–200k'], [200000, null, 'Trên 200k']] as $r)
                    <a class="sb-chip {{ ($filters['price_min'] ?? '') == $r[0] && ($filters['price_max'] ?? '') == $r[1] ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['price_min' => $r[0] ?: null, 'price_max' => $r[1], 'page' => null]) }}">{{ $r[2] }}</a>
                @endforeach
            </div>
        </div>
        <div class="sb-filter-group">
            <label class="sb-label"><i class="fas fa-book"></i> Phiên bản</label>
            <div class="sb-chip-list">
                <label class="sb-chip {{ empty($filters['format']) ? 'active' : '' }}"><input type="radio" name="format" value="" @if(empty($filters['format'])) checked @endif onchange="this.form.submit()">Tất cả</label>
                @foreach($formats as $fm)
                    <label class="sb-chip {{ ($filters['format'] ?? '') == $fm ? 'active' : '' }}"><input type="radio" name="format" value="{{ $fm }}" @if(($filters['format'] ?? '') == $fm) checked @endif onchange="this.form.submit()">{{ $fm }}</label>
                @endforeach
            </div>
        </div>
        <div class="sb-filter-group">
            <label class="sb-label"><i class="fas fa-star"></i> Đánh giá</label>
            <div class="sb-chip-list">
                @foreach(['' => 'Tất cả', '4' => '4★ trở lên', '3' => '3★ trở lên'] as $k => $l)
                    <label class="sb-chip {{ (string) ($filters['rating'] ?? '') === (string) $k ? 'active' : '' }}"><input type="radio" name="rating" value="{{ $k }}" @if((string) ($filters['rating'] ?? '') === (string) $k) checked @endif onchange="this.form.submit()">{{ $l }}</label>
                @endforeach
            </div>
            <label class="sb-check mt-3"><input type="checkbox" name="in_stock" value="1" @if(!empty($filters['in_stock'])) checked @endif onchange="this.form.submit()"> Chỉ hiện sách còn hàng</label>
        </div>
        <input type="hidden" name="sort" value="{{ $filters['sort'] ?? '' }}">
        <div class="sb-filter-foot">
            <button class="sb-btn sb-btn-block"><i class="fas fa-filter"></i> Áp dụng</button>
            <a class="sb-btn sb-btn-soft sb-btn-block" href="{{ route('books.smart') }}"><i class="fas fa-magic"></i> Tìm bằng câu nói (AI)</a>
        </div>
    </form>

    <div>
        <div class="sb-toolbar">
            <div><span class="sb-eyebrow">Kết quả</span><h2>{{ $title }}</h2></div>
            <span class="sb-count-pill ms-auto"><b>{{ $books->total() }}</b> đầu sách</span>
            <select class="sb-control" style="width:auto; height:42px" onchange="location.href=this.value" aria-label="Sắp xếp">
                @foreach($sorts as $k => $l)
                    <option value="{{ request()->fullUrlWithQuery(['sort' => $k ?: null, 'page' => null]) }}" @if(($filters['sort'] ?? '') == $k) selected @endif>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        @php $active = collect($filters)->only(array_keys($labels))->filter(function ($v) { return $v !== null && $v !== ''; }); @endphp
        @if($active->count())
            <div class="sb-active-filters">
                @foreach($active as $k => $v)
                    <a href="{{ request()->fullUrlWithQuery([$k => null, 'page' => null]) }}">{{ $labels[$k] }}: {{ $k === 'in_stock' ? 'có' : $v }} <i class="fas fa-times"></i></a>
                @endforeach
            </div>
        @endif

        @if($books->count())
            <div class="sb-products cot-3">
                @foreach($books as $book)@include('partials.book-card')@endforeach
            </div>
            {{ $books->links('partials.pagination') }}
        @else
            <div class="sb-card sb-empty"><i class="fas fa-book-dead"></i><h3>Không tìm thấy sách phù hợp</h3><p>Thử bỏ bớt điều kiện lọc hoặc dùng <a href="{{ route('books.smart') }}">tìm kiếm thông minh</a>.</p></div>
        @endif
    </div>
</div>
@endsection
