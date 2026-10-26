@extends('layouts.admin')
@section('title', 'Knowledge Graph sản phẩm')

@section('content')
<div class="row g-3">
    <div class="col-lg-9"><div class="card"><div class="card-body">
        <form class="d-flex gap-2 mb-2 align-items-center small">
            <span>Lọc theo thể loại:</span>
            <select name="category_id" class="form-select form-select-sm" style="max-width:260px" onchange="this.form.submit()">
                <option value="">Toàn bộ</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @if(request('category_id') == $c->id) selected @endif>{{ $c->name }}</option>
                    @foreach($c->children as $ch)<option value="{{ $ch->id }}" @if(request('category_id') == $ch->id) selected @endif>— {{ $ch->name }}</option>@endforeach
                @endforeach
            </select>
            <span class="ms-auto">
                <span class="badge" style="background:#2F6BFF">Sách</span>
                <span class="badge" style="background:#12A15E">Tác giả</span>
                <span class="badge" style="background:#FF6A1A">Thể loại</span>
                <span class="badge" style="background:#7B5CFF">NXB</span>
            </span>
        </form>
        <div id="graph" style="height:620px; border:1px solid #E8EDF5; border-radius:.5rem; background:#FFFFFF"></div>
        <p class="small text-muted mt-2 mb-0">Kéo/thả, cuộn để phóng to. Nhấp đúp vào nút sách để mở trang sách. Quan hệ: Tác giả —viết→ Sách —thuộc→ Thể loại —con của→ Thể loại cha; NXB —xuất bản→ Sách.</p>
    </div></div></div>
    <div class="col-lg-3">
        <div class="card mb-3"><div class="card-body small">
            <h2 class="h6 fw-bold">Quy mô đồ thị</h2>
            @foreach($summary as $k => $v)<div class="d-flex justify-content-between"><span>{{ ['books' => 'Sách', 'authors' => 'Tác giả', 'categories' => 'Thể loại', 'publishers' => 'NXB'][$k] }}</span><strong>{{ $v }}</strong></div>@endforeach
            <div class="d-flex justify-content-between"><span>Số cạnh hiển thị</span><strong>{{ count($graph['edges']) }}</strong></div>
        </div></div>
        <div class="card"><div class="card-body small" style="max-height:470px; overflow:auto">
            <h2 class="h6 fw-bold">Tri thức suy ra</h2>
            @foreach($facts as $f)
                <div class="border-bottom py-1"><strong>{{ $f['author'] }}</strong> viết {{ $f['books'] }} cuốn thuộc <em>{{ $f['categories'] }}</em>, phát hành bởi {{ $f['publishers'] }}.</div>
            @endforeach
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/vis-network.min.js') }}"></script>
<script>
(function () {
    var data = @json($graph);
    var bookUrl = @json(url('/sach'));
    var colors = {book: '#2F6BFF', author: '#12A15E', category: '#FF6A1A', publisher: '#7B5CFF'};
    var shapes = {book: 'box', author: 'ellipse', category: 'diamond', publisher: 'hexagon'};
    var nodes = new vis.DataSet(data.nodes.map(function (n) {
        return Object.assign({}, n, {color: {background: colors[n.group], border: colors[n.group]}, font: {color: n.group === 'book' || n.group === 'publisher' ? '#fff' : '#0B1B33', size: 12}, shape: shapes[n.group], size: n.group === 'category' ? 18 : 14});
    }));
    var edges = new vis.DataSet(data.edges.map(function (e, i) { return Object.assign({id: i, arrows: 'to', font: {size: 9, color: '#66748A'}, color: '#C9D3E3'}, e); }));
    var net = new vis.Network(document.getElementById('graph'), {nodes: nodes, edges: edges}, {
        physics: {barnesHut: {gravitationalConstant: -6000, springLength: 140}, stabilization: {iterations: 200}},
        interaction: {hover: true}
    });
    net.on('doubleClick', function (p) {
        if (p.nodes.length && String(p.nodes[0]).charAt(0) === 'b') {
            var n = nodes.get(p.nodes[0]);
            window.open(bookUrl + '?q=' + encodeURIComponent(n.title.replace(/ \(\d+\)$/, '')), '_blank');
        }
    });
})();
</script>
@endpush
