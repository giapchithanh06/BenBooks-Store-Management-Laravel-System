<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - BenBooks</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}">
</head>
<body>
<section class="sb-top-banner" style="min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; margin:0">
    <a href="{{ url('/') }}"><img src="{{ asset('assets/img/logo-motsach.svg') }}" alt="BenBooks" style="height:70px"></a>
    <h1 style="font-size:6rem; margin-top:20px">404</h1>
    <span class="sb-eyebrow"><i class="fas fa-book-dead"></i> Không tìm thấy trang</span>
    <p style="color:rgba(255,255,255,.75); max-width:52ch; margin:16px auto 26px">{{ isset($exception) && $exception->getMessage() && 404 == 403 ? $exception->getMessage() : 'Có thể cuốn sách đã ngừng kinh doanh hoặc đường dẫn bị sai.' }}</p>
    <div class="sb-hero-actions"><a class="sb-btn sb-btn-lg" href="{{ url('/') }}"><i class="fas fa-home"></i> Về trang chủ</a><a class="sb-btn sb-btn-white sb-btn-lg" href="javascript:history.back()">Quay lại</a></div>
</section>
</body>
</html>
