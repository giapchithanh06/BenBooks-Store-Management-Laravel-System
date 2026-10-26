@extends('layouts.admin')
@section('title', 'Kho tri thức (RAG)')

@section('content')
<div class="row g-3">
    <div class="col-lg-7"><div class="card"><div class="card-body">
        <div class="d-flex justify-content-between mb-2"><h2 class="h6 fw-bold m-0">Tài liệu ({{ $docs->count() }})</h2><a href="{{ route('admin.ai.knowledge.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Thêm tài liệu</a></div>
        <table class="table table-sm small align-middle">
            @foreach($docs as $d)
                <tr class="{{ $d->is_active ? '' : 'opacity-50' }}"><td><strong>{{ $d->title }}</strong><div class="text-muted">{{ \Illuminate\Support\Str::limit($d->content, 120) }}</div></td>
                    <td><span class="badge text-bg-light border">{{ $d->category_name }}</span></td>
                    <td class="text-nowrap"><a href="{{ route('admin.ai.knowledge.edit', $d) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i></a>
                        <form method="post" action="{{ route('admin.ai.knowledge.destroy', $d) }}" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form></td></tr>
            @endforeach
        </table>
    </div></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-body">
        <h2 class="h6 fw-bold">Thử truy xuất (Retrieval) & trả lời</h2>
        <form class="d-flex gap-1 mb-2"><input name="q" value="{{ optional($test)['q'] }}" class="form-control form-control-sm" placeholder="VD: đổi trả trong bao lâu?"><button class="btn btn-sm btn-primary">Hỏi</button></form>
        @if($test)
            <div class="small fw-semibold">Top đoạn văn truy xuất (cosine TF-IDF):</div>
            @forelse($test['hits'] as $h)<div class="border rounded p-2 my-1 small"><span class="badge text-bg-secondary">{{ number_format($h['score'], 3) }}</span> <em>{{ $h['doc']->title }}</em><div>{{ $h['text'] }}</div></div>@empty<div class="text-muted small">Không có đoạn nào đủ liên quan.</div>@endforelse
            <div class="small fw-semibold mt-2">Câu trả lời của chatbot (intent: {{ $test['answer']['intent'] }}):</div>
            <div class="alert alert-light border small" style="white-space:pre-line">{{ $test['answer']['answer'] }}</div>
        @endif
        <p class="small text-muted mb-0">Muốn câu trả lời diễn đạt tự nhiên bằng mô hình ngôn ngữ, thêm <code>LLM_API_KEY</code> vào <code>.env</code>; không có khóa hệ thống vẫn chạy offline bằng trích xuất câu.</p>
    </div></div></div>
</div>
@endsection
