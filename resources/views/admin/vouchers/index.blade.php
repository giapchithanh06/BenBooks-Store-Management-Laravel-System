@extends('layouts.admin')
@section('title', 'Mã giảm giá')

@section('content')
<div class="card"><div class="card-body">
    <div class="text-end mb-2"><a href="{{ route('admin.vouchers.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Tạo mã</a></div>
    <table class="table table-sm align-middle small">
        <thead><tr><th>Mã</th><th>Chương trình</th><th>Ưu đãi</th><th>Thời gian</th><th class="text-center">Đã dùng</th><th>TT</th><th></th></tr></thead>
        @foreach($vouchers as $v)
            <tr>
                <td><code class="fs-6">{{ $v->code }}</code></td>
                <td>{{ $v->name }}</td>
                <td>{{ $v->description }}<div class="text-muted">Tối đa {{ $v->per_user_limit }} lần/khách</div></td>
                <td>{{ $v->starts_at ? $v->starts_at->format('d/m/Y') : '…' }} → {{ $v->ends_at ? $v->ends_at->format('d/m/Y') : '…' }}</td>
                <td class="text-center">{{ $v->used_count }}{{ $v->usage_limit ? ' / ' . $v->usage_limit : '' }}</td>
                <td>
                    @if(!$v->is_active)<span class="badge text-bg-secondary">Tắt</span>
                    @elseif($v->ends_at && $v->ends_at->isPast())<span class="badge text-bg-dark">Hết hạn</span>
                    @else<span class="badge text-bg-success">Hoạt động</span>@endif
                </td>
                <td class="text-nowrap">
                    <a href="{{ route('admin.vouchers.edit', $v) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i></a>
                    <form method="post" action="{{ route('admin.vouchers.destroy', $v) }}" class="d-inline" onsubmit="return confirm('Xóa mã này?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form>
                </td>
            </tr>
        @endforeach
    </table>
    {{ $vouchers->links('partials.pagination') }}
</div></div>
@endsection
