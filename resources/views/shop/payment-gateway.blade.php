<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cổng thanh toán MotPay (Sandbox)</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/motsach.css') }}">
</head>
<body style="background: var(--sb-gray)">
<section class="sb-top-banner" style="margin-bottom:0; padding:50px 20px 90px">
    <span class="sb-eyebrow"><i class="fas fa-lock"></i> Cổng thanh toán mô phỏng</span>
    <h1>MotPay Sandbox</h1>
</section>
<div class="container" style="max-width:760px; margin-top:-60px; position:relative; padding-bottom:60px">
    <div class="sb-card sb-card-pad">
        <div class="sb-alert warn mt-0"><i class="fas fa-info-circle mt-1"></i><div>Đây là cổng thanh toán <b>MÔ PHỎNG</b> phục vụ demo đồ án (ký số HMAC-SHA512 giống VNPay) – không phát sinh giao dịch thật.</div></div>
        <div class="sb-qr">
            <div class="text-center">
                <div class="sb-success-icon" style="width:120px;height:120px;font-size:3rem"><i class="fas fa-qrcode"></i></div>
            </div>
            <dl>
                <dt>Nhà cung cấp</dt><dd>BenBooks</dd>
                <dt>Mã đơn hàng</dt><dd>{{ $params['order_code'] }}</dd>
                <dt>Nội dung</dt><dd>{{ $params['info'] }}</dd>
                <dt>Mã giao dịch</dt><dd>{{ $params['txn_ref'] }}</dd>
                <dt>Số tiền</dt><dd><span class="sb-price" style="font-size:1.6rem">{{ money($params['amount']) }}</span></dd>
            </dl>
        </div>
        <form method="post" action="{{ route('payment.gateway.submit') }}" class="mt-4">
            @csrf
            @foreach($params as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <div class="sb-field"><label>Chọn ngân hàng / ví</label></div>
            <div class="row g-2 mb-3">
                @foreach($banks as $code => $name)
                    <div class="col-6 col-md-4">
                        <label class="sb-pay-option mb-0 {{ $loop->first ? 'checked' : '' }}" onclick="document.querySelectorAll('.sb-pay-option').forEach(function(l){l.classList.remove('checked')});this.classList.add('checked')">
                            <input type="radio" name="bank_code" value="{{ $code }}" @if($loop->first) checked @endif>
                            <span><strong>{{ $code }}</strong><small>{{ $name }}</small></span>
                        </label>
                    </div>
                @endforeach
            </div>
            <div class="sb-note-box mb-3">Thẻ test: <b>9704 1985 2619 1432 198</b> · Chủ thẻ: <b>NGUYEN VAN A</b> · OTP: <b>123456</b></div>
            <div class="sb-buy-row">
                <button name="action" value="pay" class="sb-btn sb-btn-lg"><i class="fas fa-check-circle"></i> Xác nhận thanh toán</button>
                <button name="action" value="cancel" class="sb-btn sb-btn-outline sb-btn-lg">Hủy giao dịch</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
