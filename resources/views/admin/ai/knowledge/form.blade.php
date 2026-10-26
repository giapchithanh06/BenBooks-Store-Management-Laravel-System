@extends('layouts.admin')
@section('title', $doc->exists ? 'Sửa tài liệu' : 'Thêm tài liệu')

@section('content')
<form method="post" action="{{ $doc->exists ? route('admin.ai.knowledge.update', $doc) : route('admin.ai.knowledge.store') }}" class="card" style="max-width:860px"><div class="card-body">
    @csrf
    @if($doc->exists) @method('PUT') @endif
    <div class="row g-2 mb-2">
        <div class="col-md-8"><label class="form-label">Tiêu đề</label><input name="title" value="{{ old('title', $doc->title) }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Nhóm</label><select name="category" class="form-select">@foreach(\App\Models\KnowledgeDoc::CATEGORIES as $k => $l)<option value="{{ $k }}" @if(old('category', $doc->category) == $k) selected @endif>{{ $l }}</option>@endforeach</select></div>
    </div>
    <label class="form-label">Nội dung <small class="text-muted">(mỗi câu/dòng là một đoạn để truy xuất)</small></label>
    <textarea name="content" rows="12" class="form-control mb-2" required>{{ old('content', $doc->content) }}</textarea>
    <div class="form-check form-switch mb-3"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="ka" @if(old('is_active', $doc->is_active)) checked @endif><label class="form-check-label" for="ka">Sử dụng cho chatbot</label></div>
    <button class="btn btn-primary">Lưu</button> <a href="{{ route('admin.ai.knowledge.index') }}" class="btn btn-link">Hủy</a>
</div></form>
@endsection
