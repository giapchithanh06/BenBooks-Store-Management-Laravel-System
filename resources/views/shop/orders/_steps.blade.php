@php
    $flow = ['pending' => ['fa-receipt', 'Đặt hàng'], 'confirmed' => ['fa-clipboard-check', 'Đã xác nhận'], 'shipping' => ['fa-truck', 'Đang giao'], 'delivered' => ['fa-home', 'Đã nhận hàng']];
    $keys = array_keys($flow);
    $current = array_search($order->status, $keys);
@endphp
@if(in_array($order->status, ['cancelled', 'returned']))
    <div class="sb-alert loi my-0"><i class="fas fa-times-circle mt-1"></i><div>Đơn hàng {{ mb_strtolower($order->status_name) }}@if($order->cancel_reason) – {{ $order->cancel_reason }}@endif</div></div>
@else
    <ul class="sb-timeline bon">
        @foreach($flow as $k => $s)
            <li class="{{ $current !== false && array_search($k, $keys) <= $current ? 'xong' : '' }}"><i class="fas {{ $s[0] }}"></i>{{ $s[1] }}</li>
        @endforeach
    </ul>
@endif
