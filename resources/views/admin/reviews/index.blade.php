@extends('layouts.admin')
@section('title', 'Đánh giá sản phẩm')

@section('content')
<div class="card"><div class="card-body">
    <form class="row g-2 mb-3">
        <div class="col-md-3"><select name="sentiment" class="form-select form-select-sm"><option value="">Cảm xúc (AI)</option>@foreach(\App\Models\Review::SENTIMENTS as $k => $l)<option value="{{ $k }}" @if(request('sentiment') == $k) selected @endif>{{ $l }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="rating" class="form-select form-select-sm"><option value="">Số sao</option>@for($i = 5; $i >= 1; $i--)<option value="{{ $i }}" @if(request('rating') == $i) selected @endif>{{ $i }} sao</option>@endfor</select></div>
        <div class="col-md-2"><button class="btn btn-sm btn-secondary">Lọc</button></div>
    </form>
    <table class="table table-sm align-middle small">
        <thead><tr><th>Sách</th><th>Khách hàng</th><th>Đánh giá</th><th>Cảm xúc (AI)</th><th>Ngày</th><th></th></tr></thead>
        @foreach($reviews as $r)
            <tr class="{{ $r->is_visible ? '' : 'opacity-50' }}">
                <td>{{ optional($r->book)->title }}</td>
                <td>{{ optional($r->user)->name }}</td>
                <td style="max-width:380px"><span class="sb-stars">{!! stars($r->rating) !!}</span><div>{{ $r->comment }}</div>
                    @if($r->aspects)<div class="mt-1">@foreach($r->aspects as $a)<span class="badge text-bg-{{ \App\Models\Review::SENTIMENT_COLORS[$a['sentiment']] ?? 'light' }} me-1">{{ $a['label'] }}</span>@endforeach</div>@endif
                </td>
                <td><span class="badge text-bg-{{ $r->sentiment_color }}">{{ $r->sentiment_name }}</span><div class="text-muted">{{ $r->sentiment_score }}</div></td>
                <td>{{ $r->created_at->format('d/m/Y') }}</td>
                <td class="text-nowrap">
                    <form method="post" action="{{ route('admin.reviews.toggle', $r) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary" title="Ẩn/hiện"><i class="fas {{ $r->is_visible ? 'fa-eye-slash' : 'fa-eye' }}"></i></button></form>
                    <form method="post" action="{{ route('admin.reviews.destroy', $r) }}" class="d-inline" onsubmit="return confirm('Xóa đánh giá?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button></form>
                </td>
            </tr>
        @endforeach
    </table>
    {{ $reviews->links('partials.pagination') }}
</div></div>
@endsection
