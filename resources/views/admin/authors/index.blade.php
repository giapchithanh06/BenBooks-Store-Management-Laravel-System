@extends('layouts.admin')
@section('title', 'Tác giả')

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <form class="mb-2"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm tác giả…"></form>
            <table class="table align-middle small">
                <thead><tr><th>Tác giả</th><th>Tiểu sử</th><th class="text-center">Số sách</th><th></th></tr></thead>
                @foreach($authors as $a)
                    <tr>
                        <td><strong>{{ $a->name }}</strong><div class="text-muted"><a href="{{ route('books.author', $a->slug) }}" target="_blank">/{{ $a->slug }}</a></div></td>
                        <td class="text-muted">{{ \Illuminate\Support\Str::limit($a->bio, 80) }}</td>
                        <td class="text-center">{{ $a->books_count }}</td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#au{{ $a->id }}"><i class="fas fa-pen"></i></button>
                            <form method="post" action="{{ route('admin.authors.destroy', $a) }}" class="d-inline" onsubmit="return confirm('Xóa tác giả?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form>
                            <div class="modal fade text-start" id="au{{ $a->id }}" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('admin.authors.update', $a) }}">
                                @csrf @method('PUT')
                                <div class="modal-header"><h5 class="modal-title">Sửa tác giả</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-2"><label class="form-label small">Tên</label><input name="name" value="{{ $a->name }}" class="form-control" required></div>
                                    <div><label class="form-label small">Tiểu sử</label><textarea name="bio" rows="4" class="form-control">{{ $a->bio }}</textarea></div>
                                </div>
                                <div class="modal-footer"><button class="btn btn-primary">Lưu</button></div>
                            </form></div></div>
                        </td>
                    </tr>
                @endforeach
            </table>
            {{ $authors->links('partials.pagination') }}
        </div></div>
    </div>
    <div class="col-lg-4">
        <form method="post" action="{{ route('admin.authors.store') }}" class="card"><div class="card-body">
            @csrf
            <h2 class="h6 fw-bold">Thêm tác giả</h2>
            <div class="mb-2"><label class="form-label small">Tên *</label><input name="name" class="form-control form-control-sm" required></div>
            <div class="mb-3"><label class="form-label small">Tiểu sử</label><textarea name="bio" rows="4" class="form-control form-control-sm"></textarea></div>
            <button class="btn btn-primary w-100">Thêm</button>
        </div></form>
    </div>
</div>
@endsection
