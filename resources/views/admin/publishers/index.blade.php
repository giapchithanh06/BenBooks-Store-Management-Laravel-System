@extends('layouts.admin')
@section('title', 'Nhà xuất bản')

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <form class="mb-2"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm NXB…"></form>
            <table class="table align-middle small">
                <thead><tr><th>Nhà xuất bản</th><th>Địa chỉ</th><th>Website</th><th class="text-center">Số sách</th><th></th></tr></thead>
                @foreach($publishers as $p)
                    <tr>
                        <td><strong>{{ $p->name }}</strong></td>
                        <td>{{ $p->address }}</td>
                        <td>@if($p->website)<a href="{{ $p->website }}" target="_blank">{{ $p->website }}</a>@endif</td>
                        <td class="text-center">{{ $p->books_count }}</td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#pu{{ $p->id }}"><i class="fas fa-pen"></i></button>
                            <form method="post" action="{{ route('admin.publishers.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Xóa NXB?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form>
                            <div class="modal fade text-start" id="pu{{ $p->id }}" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('admin.publishers.update', $p) }}">
                                @csrf @method('PUT')
                                <div class="modal-header"><h5 class="modal-title">Sửa nhà xuất bản</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-2"><label class="form-label small">Tên</label><input name="name" value="{{ $p->name }}" class="form-control" required></div>
                                    <div class="mb-2"><label class="form-label small">Địa chỉ</label><input name="address" value="{{ $p->address }}" class="form-control"></div>
                                    <div><label class="form-label small">Website</label><input name="website" value="{{ $p->website }}" class="form-control" placeholder="https://"></div>
                                </div>
                                <div class="modal-footer"><button class="btn btn-primary">Lưu</button></div>
                            </form></div></div>
                        </td>
                    </tr>
                @endforeach
            </table>
            {{ $publishers->links('partials.pagination') }}
        </div></div>
    </div>
    <div class="col-lg-4">
        <form method="post" action="{{ route('admin.publishers.store') }}" class="card"><div class="card-body">
            @csrf
            <h2 class="h6 fw-bold">Thêm nhà xuất bản</h2>
            <div class="mb-2"><label class="form-label small">Tên *</label><input name="name" class="form-control form-control-sm" required></div>
            <div class="mb-2"><label class="form-label small">Địa chỉ</label><input name="address" class="form-control form-control-sm"></div>
            <div class="mb-3"><label class="form-label small">Website</label><input name="website" class="form-control form-control-sm" placeholder="https://"></div>
            <button class="btn btn-primary w-100">Thêm</button>
        </div></form>
    </div>
</div>
@endsection
