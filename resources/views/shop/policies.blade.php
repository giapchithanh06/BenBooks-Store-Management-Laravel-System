@extends('layouts.shop')
@section('title', 'Chính sách')

@section('content')
@include('partials.page-head', ['eyebrow' => '<i class="fas fa-shield-alt"></i> Hỗ trợ khách hàng', 'title' => 'Chính sách & câu hỏi thường gặp',
    'desc' => 'Trợ lý chat ở góc phải màn hình trả lời dựa trên chính các tài liệu này (RAG).'])
@php
    $anchors = ['Chính sách đổi trả hàng' => 'doi-tra', 'Chính sách giao hàng' => 'giao-hang', 'Phương thức thanh toán' => 'thanh-toan'];
    $icons = ['policy' => 'fa-file-contract', 'guide' => 'fa-compass', 'faq' => 'fa-question', 'about' => 'fa-info'];
    $all = $docs->flatten(1);
@endphp
<nav class="sb-policy-nav">
    @foreach($all as $d)<a href="#{{ $anchors[$d->title] ?? 'doc-' . $d->id }}">{{ $d->title }}</a>@endforeach
</nav>
<div class="sb-card sb-card-pad">
    <div class="sb-prose" style="max-width:none">
        @foreach($all as $d)
            <div class="sb-policy-block" id="{{ $anchors[$d->title] ?? 'doc-' . $d->id }}">
                <h2><i class="fas {{ $icons[$d->category] ?? 'fa-book' }}"></i> {{ $d->title }}</h2>
                <ul>@foreach(\App\Services\TextAnalyzer::sentences($d->content) as $s)<li>{{ $s }}</li>@endforeach</ul>
            </div>
        @endforeach
    </div>
</div>
@endsection
