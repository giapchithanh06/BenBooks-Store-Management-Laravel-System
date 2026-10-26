@extends('layouts.admin')
@section('title', 'Sách & truyện')

@section('content')
<div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
        <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tên sách hoặc ISBN"></div>
        <div class="col-md-3">
            <select name="category_id" class="form-select form-select-sm">
                <option value="">-- Thể loại --</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @if(request('category_id') == $c->id) selected @endif>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">-- Trạng thái --</option>
                <option value="active" @if(request('status') == 'active') selected @endif>Đang bán</option>
                <option value="hidden" @if(request('status') == 'hidden') selected @endif>Đang ẩn</option>
                <option value="trashed" @if(request('status') == 'trashed') selected @endif>Đã xóa</option>
            </select>
        </div>
        <div class="col-md-1"><button class="btn btn-sm btn-secondary w-100">Lọc</button></div>
        <div class="col-md-2 text-end"><a href="{{ route('admin.books.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Thêm sách</a></div>
    </form>
    <div class="table-responsive">
    <table class="table table-hover align-middle small">
        <thead><tr><th></th><th>Tên sách</th><th>Tác giả</th><th>Thể loại</th><th>Phiên bản / giá</th><th class="text-center">Tồn</th><th class="text-center">Đã bán</th><th>TT</th><th></th></tr></thead>
        <tbody>
        @forelse($books as $b)
            <tr>
                <td><img src="{{ $b->cover_url }}" width="36" class="rounded" alt=""></td>
                <td><strong>{{ $b->title }}</strong><div class="text-muted">ISBN {{ $b->isbn ?: '—' }} · {{ optional($b->publisher)->name }} · {{ $b->publish_year }}</div></td>
                <td>{{ $b->author_names }}</td>
                <td>{{ optional($b->category)->name }}</td>
                <td>@foreach($b->variants as $v)<div>{{ $v->name }}: {{ money($v->price) }}</div>@endforeach</td>
                <td class="text-center"><span class="badge text-bg-{{ $b->total_stock <= 5 ? 'danger' : 'light' }}">{{ $b->total_stock }}</span></td>
                <td class="text-center">{{ $b->sold_count }}</td>
                <td>
                    @if($b->trashed())<span class="badge text-bg-dark">Đã xóa</span>
                    @else<span class="badge text-bg-{{ $b->is_active ? 'success' : 'secondary' }}">{{ $b->is_active ? 'Đang bán' : 'Ẩn' }}</span>@if($b->is_featured) <i class="fas fa-star text-warning" title="Nổi bật"></i>@endif
                    @endif
                </td>
                <td class="text-nowrap">
                    @unless($b->trashed())
                        <a href="{{ route('books.show', $b->slug) }}" target="_blank" class="btn btn-sm btn-light" title="Xem"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('admin.books.edit', $b) }}" class="btn btn-sm btn-outline-primary" title="Sửa"><i class="fas fa-pen"></i></a>
                        <form action="{{ route('admin.books.destroy', $b) }}" method="post" class="d-inline" onsubmit="return confirm('Xóa sách này?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa"><i class="fas fa-trash-alt"></i></button></form>
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-muted">Không có sách.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $books->links('partials.pagination') }}
</div></div>
@endsection
