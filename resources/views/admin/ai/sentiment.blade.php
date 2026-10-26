@extends('layouts.admin')
@section('title', 'Phân tích cảm xúc đánh giá')

@section('content')
<div class="row g-3 mb-3">
    @foreach(\App\Models\Review::SENTIMENTS as $k => $l)
        <div class="col-md-4"><div class="kpi border-start border-4 border-{{ \App\Models\Review::SENTIMENT_COLORS[$k] }}"><small class="text-muted">{{ $l }}</small><div class="fs-3 fw-bold">{{ $counts[$k] ?? 0 }}</div></div></div>
    @endforeach
</div>
<div class="row g-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-body">
        <h2 class="h6 fw-bold">Phân tích theo khía cạnh (Aspect-based)</h2>
        <canvas id="aspChart" height="200"></canvas>
    </div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-body">
        <h2 class="h6 fw-bold">Thử phân tích một câu</h2>
        <form class="mb-2"><textarea name="text" rows="3" class="form-control form-control-sm" placeholder="VD: Truyện rất hay nhưng giấy mỏng, giao hàng hơi chậm">{{ optional($test)['text'] }}</textarea>
            <button class="btn btn-sm btn-primary mt-2">Phân tích</button>
            <a href="?reanalyze=1" class="btn btn-sm btn-outline-secondary mt-2">Phân tích lại toàn bộ review</a></form>
        @if($test)
            <div>Điểm cảm xúc: <strong class="{{ $test['score'] > 0.05 ? 'text-success' : ($test['score'] < -0.05 ? 'text-danger' : '') }}">{{ $test['score'] }}</strong> (-1 → 1)</div>
            @foreach($test['aspects'] as $a)<span class="badge text-bg-{{ \App\Models\Review::SENTIMENT_COLORS[$a['sentiment']] }} me-1">{{ $a['label'] }}: {{ $a['score'] }}</span>@endforeach
        @endif
        <p class="small text-muted mt-2 mb-0">Mô hình: từ điển cảm xúc tiếng Việt (đã bỏ dấu) + xử lý phủ định ("không hay") và từ nhấn mạnh ("rất", "cực kỳ"), kết hợp số sao (60% văn bản – 40% số sao). Câu được tách theo dấu câu và từ nối "nhưng" để chấm từng khía cạnh.</p>
    </div></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-body">
        <h2 class="h6 fw-bold">Sách có cảm xúc thấp nhất</h2>
        <table class="table table-sm small mb-0"><thead><tr><th>Sách</th><th>Review</th><th>Tiêu cực</th><th>Điểm TB</th></tr></thead>
            @foreach($byBook as $b)<tr><td>{{ $b->title }}</td><td>{{ $b->total }}</td><td>{{ $b->negatives }}</td><td>{{ number_format($b->score, 2) }}</td></tr>@endforeach
        </table>
    </div></div></div>
    <div class="col-lg-7"><div class="card"><div class="card-body">
        <h2 class="h6 fw-bold">Đánh giá mới nhất</h2>
        @foreach($latest as $r)
            <div class="border-bottom py-1 small">
                <span class="badge text-bg-{{ $r->sentiment_color }}">{{ $r->sentiment_name }}</span> <span class="sb-stars">{!! stars($r->rating) !!}</span> <strong>{{ optional($r->book)->title }}</strong>
                <div>{{ $r->comment }}</div>
            </div>
        @endforeach
    </div></div></div>
</div>
@endsection

@push('scripts')
@php
    $aLabels = array_values(array_map(function ($a) { return $a['label']; }, $aspects));
    $aPos = array_values(array_map(function ($a) { return $a['positive']; }, $aspects));
    $aNeu = array_values(array_map(function ($a) { return $a['neutral']; }, $aspects));
    $aNeg = array_values(array_map(function ($a) { return $a['negative']; }, $aspects));
@endphp
<script>
new Chart(document.getElementById('aspChart'), {type: 'bar', data: {labels: @json($aLabels), datasets: [
    {label: 'Tích cực', data: @json($aPos), backgroundColor: '#12A15E'},
    {label: 'Trung lập', data: @json($aNeu), backgroundColor: '#98A2B3'},
    {label: 'Tiêu cực', data: @json($aNeg), backgroundColor: '#2F6BFF'}
]}, options: {indexAxis: 'y', scales: {x: {stacked: true}, y: {stacked: true}}}});
</script>
@endpush
