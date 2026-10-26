@extends('layouts.admin')
@section('title', 'Semantic Search – phòng thử nghiệm')

@section('content')
<div class="card mb-3"><div class="card-body">
    <form class="d-flex gap-2">
        <input name="q" value="{{ $q }}" class="form-control" placeholder="Nhập câu tìm kiếm tự nhiên, VD: truyện tranh Nhật về ninja dưới 30k">
        <button class="btn btn-primary">Phân tích</button>
        <a href="?rebuild=1" class="btn btn-outline-secondary text-nowrap"><i class="fas fa-sync-alt"></i> Xây lại index ({{ $indexSize }})</a>
    </form>
    <p class="small text-muted mt-2 mb-0">Mỗi đầu sách được lưu một vector TF-IDF trong bảng <code>book_vectors</code> (vector database đơn giản). Truy vấn được tách token (unigram + bigram, bỏ dấu, bỏ stopword), mở rộng đồng nghĩa, rồi xếp hạng bằng độ tương đồng cosine.</p>
</div></div>
@if($result)
<div class="row g-3">
    <div class="col-lg-4"><div class="card"><div class="card-body small">
        <h2 class="h6 fw-bold">1. Điều kiện trích xuất</h2>
        @forelse($understood as $u)<div>• {{ $u }}</div>@empty<div class="text-muted">Không có</div>@endforelse
        <h2 class="h6 fw-bold mt-3">2. Token</h2>
        @foreach($tokens as $t)<span class="badge text-bg-light border me-1">{{ $t }}</span>@endforeach
        <h2 class="h6 fw-bold mt-3">3. Mở rộng đồng nghĩa</h2>
        @forelse($expanded as $t)<span class="badge text-bg-warning me-1">{{ $t }}</span>@empty<span class="text-muted">Không có</span>@endforelse
    </div></div></div>
    <div class="col-lg-8"><div class="card"><div class="card-body">
        <h2 class="h6 fw-bold">4. Kết quả xếp hạng</h2>
        <table class="table table-sm small align-middle">
            <thead><tr><th>#</th><th>Sách</th><th>Thể loại</th><th class="text-end">Giá</th><th style="width:160px">Cosine</th></tr></thead>
            @foreach($result['results'] as $i => $b)
                <tr><td>{{ $i + 1 }}</td><td><a href="{{ route('books.show', $b->slug) }}" target="_blank">{{ $b->title }}</a><div class="text-muted">{{ $b->author_names }}</div></td>
                    <td>{{ optional($b->category)->name }}</td><td class="text-end">{{ money(optional($b->variants->first())->price) }}</td>
                    <td><div class="progress" style="height:14px"><div class="progress-bar bg-primary" style="width: {{ min(100, $b->score * 100) }}%"></div></div><small>{{ $b->score }}</small></td></tr>
            @endforeach
        </table>
    </div></div></div>
</div>
@endif
@endsection
