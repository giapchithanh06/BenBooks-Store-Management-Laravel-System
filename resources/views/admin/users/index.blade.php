@extends('layouts.admin')
@section('title', 'Tài khoản & phân quyền')

@section('content')
<div class="card mb-3"><div class="card-body small">
    <strong>Phân quyền:</strong>
    <span class="badge text-bg-danger">Quản trị viên</span> toàn quyền ·
    <span class="badge text-bg-primary">Nhân viên</span> sách, kho, đơn hàng, đổi trả, đánh giá ·
    <span class="badge text-bg-warning">Nhân viên giao hàng</span> app giao hàng ·
    <span class="badge text-bg-secondary">Khách hàng</span> mua sắm
</div></div>
<div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
        <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tên, email, SĐT"></div>
        <div class="col-md-3"><select name="role" class="form-select form-select-sm"><option value="">Vai trò</option>@foreach(\App\Models\User::ROLES as $k => $l)<option value="{{ $k }}" @if(request('role') == $k) selected @endif>{{ $l }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-sm btn-secondary">Lọc</button></div>
        <div class="col-md-3 text-end"><button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#userNew"><i class="fas fa-user-plus"></i> Tạo tài khoản</button></div>
    </form>
    <table class="table table-sm align-middle small">
        <thead><tr><th>Họ tên</th><th>Email / SĐT</th><th>Vai trò</th><th>Phân cụm</th><th class="text-center">Đơn</th><th>TT</th><th></th></tr></thead>
        @foreach($users as $u)
            @php $roleColor = ['admin' => 'danger', 'staff' => 'primary', 'shipper' => 'warning', 'customer' => 'secondary'][$u->role] ?? 'light'; @endphp
            <tr>
                <td><strong>{{ $u->name }}</strong><div class="text-muted">Tạo {{ $u->created_at->format('d/m/Y') }}</div></td>
                <td>{{ $u->email }}<div class="text-muted">{{ $u->phone }}</div></td>
                <td><span class="badge text-bg-{{ $roleColor }}">{{ $u->role_name }}</span></td>
                <td>{{ $u->segment }}</td>
                <td class="text-center">{{ $u->orders_count }}</td>
                <td><span class="badge text-bg-{{ $u->is_active ? 'success' : 'dark' }}">{{ $u->is_active ? 'Hoạt động' : 'Đã khóa' }}</span></td>
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#user{{ $u->id }}"><i class="fas fa-pen"></i></button>
                    <form method="post" action="{{ route('admin.users.destroy', $u) }}" class="d-inline" onsubmit="return confirm('Xóa/khóa tài khoản này?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form>
                    @include('admin.users._modal', ['id' => 'user' . $u->id, 'user' => $u])
                </td>
            </tr>
        @endforeach
    </table>
    {{ $users->links('partials.pagination') }}
</div></div>
@include('admin.users._modal', ['id' => 'userNew', 'user' => null])
@endsection
