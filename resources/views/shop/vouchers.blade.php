@extends('layouts.shop')
@section('title', 'Mã giảm giá')

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-ticket-alt"></i> Săn mã', 'title' => 'Mã giảm giá đang có', 'desc' => 'Sao chép mã và nhập ở bước giỏ hàng. Mỗi đơn áp dụng một mã.'])
<div class="ms-vouchers">
    @forelse($vouchers as $v)
        @php $out = ($v->usage_limit !== null && $v->used_count >= $v->usage_limit) || ($v->starts_at && $v->starts_at->isFuture()); @endphp
        <div class="sb-card ms-voucher {{ $out ? 'het' : '' }}">
            <span class="ico"><i class="fas fa-percent"></i></span>
            <div class="flex-grow-1">
                <b style="font-family: var(--sb-display)">{{ $v->name }}</b>
                <div class="small text-muted">{{ $v->description }}</div>
                <div class="small">@if($v->starts_at && $v->starts_at->isFuture())Bắt đầu {{ $v->starts_at->format('d/m/Y') }} · @endif HSD: {{ $v->ends_at ? $v->ends_at->format('d/m/Y') : 'không giới hạn' }}
                    @if($v->usage_limit) · còn {{ max(0, $v->usage_limit - $v->used_count) }} lượt @endif</div>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <code>{{ $v->code }}</code>
                    <button type="button" class="sb-copy" onclick="navigator.clipboard && navigator.clipboard.writeText('{{ $v->code }}'); MS.toast('Đã sao chép mã {{ $v->code }}')"><i class="far fa-copy"></i> Sao chép</button>
                </div>
            </div>
        </div>
    @empty
        <div class="sb-card sb-empty"><i class="fas fa-ticket-alt"></i><h3>Chưa có mã giảm giá</h3></div>
    @endforelse
</div>
@endsection
