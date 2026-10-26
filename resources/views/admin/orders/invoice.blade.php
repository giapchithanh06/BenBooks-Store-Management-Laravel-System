<!DOCTYPE html>
<html lang="vi"><head><meta charset="utf-8"><title>Hóa đơn {{ $order->code }}</title>
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
<style>body{font-size:14px}@media print{.no-print{display:none}}</style></head>
<body class="p-4" onload="window.print()">
<div class="d-flex justify-content-between"><div><h2 style="font-family:Georgia;color:#2F6BFF">BenBooks</h2><div>140 Lê Trọng Tấn, Tân Phú, TP.HCM · 1900 6868</div></div>
<div class="text-end"><h4>HÓA ĐƠN BÁN HÀNG</h4><div>Mã: <strong>{{ $order->code }}</strong></div><div>Ngày: {{ $order->created_at->format('d/m/Y H:i') }}</div></div></div>
<hr>
<p>Khách hàng: <strong>{{ $order->receiver_name }}</strong> · {{ $order->phone }}<br>Địa chỉ: {{ $order->address }}</p>
<table class="table table-bordered table-sm">
<thead><tr><th>#</th><th>Sản phẩm</th><th class="text-end">Đơn giá</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th></tr></thead>
@foreach($order->items as $i => $it)<tr><td>{{ $i + 1 }}</td><td>{{ $it->book_title }} ({{ $it->variant_name }})</td><td class="text-end">{{ money($it->price) }}</td><td class="text-center">{{ $it->quantity }}</td><td class="text-end">{{ money($it->total) }}</td></tr>@endforeach
<tr><td colspan="4" class="text-end">Tạm tính</td><td class="text-end">{{ money($order->subtotal) }}</td></tr>
<tr><td colspan="4" class="text-end">Giảm giá</td><td class="text-end">-{{ money($order->discount) }}</td></tr>
<tr><td colspan="4" class="text-end">Phí vận chuyển</td><td class="text-end">{{ money($order->shipping_fee) }}</td></tr>
<tr><td colspan="4" class="text-end fw-bold">TỔNG THANH TOÁN</td><td class="text-end fw-bold">{{ money($order->total) }}</td></tr>
</table>
<p>Hình thức thanh toán: {{ $order->payment_method_name }} – {{ $order->payment_status_name }}</p>
<p class="text-center mt-5">Cảm ơn quý khách đã mua sắm tại BenBooks!</p>
<button class="btn btn-secondary no-print" onclick="window.print()">In</button>
</body></html>
