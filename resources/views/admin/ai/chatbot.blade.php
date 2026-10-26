@extends('layouts.admin')
@section('title', 'Lịch sử chatbot')

@section('content')
<div class="mb-3 small">Ý định (intent) đã nhận diện:
    @foreach($intents as $k => $c)<span class="badge text-bg-light border me-1">{{ $k ?: 'khác' }}: {{ $c }}</span>@endforeach
</div>
<div class="card"><div class="card-body">
    <table class="table table-sm small align-top">
        <thead><tr><th style="width:140px">Thời gian</th><th style="width:140px">Người dùng</th><th>Câu hỏi</th><th>Trả lời</th><th>Intent</th></tr></thead>
        @foreach($messages as $m)
            <tr><td>{{ $m->created_at->format('H:i d/m/Y') }}</td><td>{{ optional($m->user)->name ?: 'Khách vãng lai' }}</td><td>{{ $m->question }}</td>
                <td style="white-space:pre-line">{{ \Illuminate\Support\Str::limit($m->answer, 300) }}</td><td><span class="badge text-bg-secondary">{{ $m->intent }}</span></td></tr>
        @endforeach
    </table>
    {{ $messages->links('partials.pagination') }}
</div></div>
@endsection
