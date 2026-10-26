<tr>
    <td>{!! $level ? '<span class="text-muted ms-3">└</span> ' : '<strong>' !!}{{ $c->name }}{!! $level ? '' : '</strong>' !!}</td>
    <td><code>{{ $c->slug }}</code></td>
    <td class="text-center">{{ $c->books_count }}</td>
    <td class="text-center">{{ $c->sort_order }}</td>
    <td class="text-end text-nowrap">
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#cat{{ $c->id }}"><i class="fas fa-pen"></i></button>
        <form method="post" action="{{ route('admin.categories.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Xóa thể loại?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form>
        <div class="modal fade text-start" id="cat{{ $c->id }}" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('admin.categories.update', $c) }}">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Sửa thể loại</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">@include('admin.categories._fields', ['cat' => $c])</div>
            <div class="modal-footer"><button class="btn btn-primary">Lưu</button></div>
        </form></div></div>
    </td>
</tr>
