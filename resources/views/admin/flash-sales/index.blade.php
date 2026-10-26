@extends('layouts.admin')
@section('title', 'Flash Sale')

@section('content')
<div class="card"><div class="card-body">
    <div class="text-end mb-2"><a href="{{ route('admin.flash-sales.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Tạo Flash Sale</a></div>
    <table class="table table-sm align-middle small">
        <thead><tr><th>Chương trình</th><th>Thời gian</th><th class="text-center">Sản phẩm</th><th class="text-center">Đã bán</th><th>Trạng thái</th><th></th></tr></thead>
        @foreach($sales as $s)
            <tr>
                <td><strong>{{ $s->name }}</strong></td>
                <td>{{ $s->starts_at->format('H:i d/m/Y') }} → {{ $s->ends_at->format('H:i d/m/Y') }}</td>
                <td class="text-center">{{ $s->items->count() }}</td>
                <td class="text-center">{{ $s->items->sum('sold') }} / {{ $s->items->sum('quantity') }}</td>
                <td><span class="badge text-bg-{{ ['Đang diễn ra' => 'success', 'Sắp diễn ra' => 'info', 'Đã kết thúc' => 'dark', 'Tạm tắt' => 'secondary'][$s->status_name] }}">{{ $s->status_name }}</span></td>
                <td class="text-nowrap">
                    <a href="{{ route('admin.flash-sales.edit', $s) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i></a>
                    <form method="post" action="{{ route('admin.flash-sales.destroy', $s) }}" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form>
                </td>
            </tr>
        @endforeach
    </table>
    {{ $sales->links('partials.pagination') }}
</div></div>
@endsection
