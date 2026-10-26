@php $cls = ['pending' => 'cho', 'confirmed' => 'xn', 'shipping' => 'giao', 'delivered' => 'xong', 'cancelled' => 'huy', 'returned' => 'tra'][$order->status] ?? 'cho'; @endphp
<span class="sb-status {{ $cls }}">{{ $order->status_name }}</span>
