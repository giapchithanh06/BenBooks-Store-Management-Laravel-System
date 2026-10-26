{{-- Banner đầu trang kiểu SmartBen: $eyebrow, $title, $desc (tùy chọn) --}}
<section class="sb-page-head reveal">
    <div>
        <span class="sb-eyebrow">{!! $eyebrow !!}</span>
        <h1>{{ $title }}</h1>
        @if(!empty($desc))<p>{{ $desc }}</p>@endif
    </div>
</section>
