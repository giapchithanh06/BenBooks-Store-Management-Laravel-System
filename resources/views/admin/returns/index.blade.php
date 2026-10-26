@extends('layouts.admin')
@section('title', 'Đổi / trả hàng')

@section('content')
<ul class="nav nav-pills small mb-3">
    <li class="nav-item"><a class="nav-link {{ !request('status') ? 'active' : '' }}" href="{{ route('admin.returns.index') }}">Tất cả</a></li>
    @foreach(\App\Models\ReturnRequest::STATUSES as $k => $l)<li class="nav-item"><a class="nav-link {{ request('status') == $k ? 'active' : '' }}" href="?status={{ $k }}">{{ $l }}</a></li>@endforeach
</ul>
@forelse($returns as $rr)
    <div class="card mb-3"><div class="card-body small">
        <div class="d-flex justify-content-between flex-wrap gap-2">
            <div><strong>{{ $rr->type_name }}</strong> · đơn <a href="{{ route('admin.orders.show', $rr->order) }}">{{ $rr->order->code }}</a> · {{ optional($rr->user)->name }} · {{ $rr->created_at->format('H:i d/m/Y') }}</div>
            <span class="badge text-bg-{{ $rr->status_color }}">{{ $rr->status_name }}</span>
        </div>
        <div class="mt-1"><strong>Lý do:</strong> {{ $rr->reason }}</div>
        <ul class="mb-1">@foreach($rr->items as $ri)<li>{{ optional($ri->orderItem)->book_title }} ({{ optional($ri->orderItem)->variant_name }}) × {{ $ri->quantity }}</li>@endforeach</ul>
        @if($rr->type === 'return')<div>Số tiền hoàn dự kiến: <strong>{{ money($rr->refund_amount) }}</strong></div>@endif
        @if($rr->admin_note)<div class="text-muted">Ghi chú: {{ $rr->admin_note }}</div>@endif
        @if(in_array($rr->status, ['pending', 'approved']))
            <form method="post" action="{{ route('admin.returns.update', $rr) }}" class="d-flex gap-2 mt-2 flex-wrap">
                @csrf
                <input name="admin_note" class="form-control form-control-sm" style="max-width:360px" placeholder="Phản hồi cho khách">
                @if($rr->status === 'pending')
                    <button name="action" value="approve" class="btn btn-sm btn-success">Duyệt</button>
                    <button name="action" value="reject" class="btn btn-sm btn-outline-danger">Từ chối</button>
                @else
                    <button name="action" value="complete" class="btn btn-sm btn-primary">Đã nhận hàng – hoàn tất {{ $rr->type === 'return' ? '& hoàn tiền' : '& gửi hàng đổi' }}</button>
                @endif
            </form>
        @endif
    </div></div>
@empty
    <div class="card"><div class="card-body text-center text-muted">Không có yêu cầu.</div></div>
@endforelse
{{ $returns->links('partials.pagination') }}
@endsection
