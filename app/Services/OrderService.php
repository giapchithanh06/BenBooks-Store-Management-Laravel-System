<?php

namespace App\Services;

use App\Jobs\ProcessNewOrder;
use App\Models\Book;
use App\Models\BookVariant;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Order;
use App\Models\UserActivity;
use App\Models\UserNotification;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected $cart;
    protected $fraud;

    public function __construct(CartService $cart, FraudDetectionService $fraud)
    {
        $this->cart = $cart;
        $this->fraud = $fraud;
    }

    /**
     * Tạo đơn từ giỏ hàng.
     * @param array $shipping receiver_name, phone, address, province, note
     * @throws \RuntimeException khi hết hàng / giỏ trống
     */
    public function placeOrder($user, array $shipping, $paymentMethod)
    {
        $items = $this->cart->items();
        if ($items->isEmpty()) {
            throw new \RuntimeException('Giỏ hàng đang trống.');
        }

        $order = DB::transaction(function () use ($user, $shipping, $paymentMethod, $items) {
            $subtotal = 0;
            $lines = [];
            foreach ($items as $it) {
                // khóa dòng để tránh bán vượt tồn kho khi nhiều người mua cùng lúc
                $variant = BookVariant::with('book')->lockForUpdate()->find($it->variant->id);
                if (!$variant || $variant->stock < $it->quantity) {
                    throw new \RuntimeException('Sách "' . $it->book->title . ' – ' . $it->variant->name . '" không đủ hàng.');
                }
                $price = (int) $variant->price;
                $flashItemId = null;
                $flash = FlashSale::currentItems()[$variant->id] ?? null;
                if ($flash) {
                    $fi = FlashSaleItem::lockForUpdate()->find($flash->id);
                    if ($fi && $fi->quantity - $fi->sold >= $it->quantity) {
                        $price = (int) $fi->sale_price;
                        $flashItemId = $fi->id;
                    }
                }
                $subtotal += $price * $it->quantity;
                $lines[] = compact('variant', 'price', 'flashItemId') + ['qty' => $it->quantity];
            }

            $voucher = null;
            $discount = 0;
            if ($code = $this->cart->voucherCode()) {
                $voucher = Voucher::where('code', $code)->lockForUpdate()->first();
                if ($voucher && !$voucher->checkUsable($subtotal, $user)) {
                    $discount = $voucher->discountFor($subtotal);
                } else {
                    $voucher = null;
                }
            }

            $shippingFee = CartService::shippingFee($subtotal, $shipping['province'] ?? null);

            $order = Order::create([
                'code'           => Order::generateCode(),
                'user_id'        => $user->id,
                'receiver_name'  => $shipping['receiver_name'],
                'phone'          => $shipping['phone'],
                'address'        => $shipping['address'],
                'note'           => $shipping['note'] ?? null,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'shipping_fee'   => $shippingFee,
                'total'          => max(0, $subtotal - $discount + $shippingFee),
                'voucher_id'     => $voucher ? $voucher->id : null,
                'voucher_code'   => $voucher ? $voucher->code : null,
                'payment_method' => $paymentMethod,
                'payment_status' => 'unpaid',
                'status'         => 'pending',
            ]);

            foreach ($lines as $l) {
                $v = $l['variant'];
                $order->items()->create([
                    'book_id'            => $v->book_id,
                    'variant_id'         => $v->id,
                    'flash_sale_item_id' => $l['flashItemId'],
                    'book_title'         => $v->book->title,
                    'variant_name'       => $v->name,
                    'price'              => $l['price'],
                    'quantity'           => $l['qty'],
                    'total'              => $l['price'] * $l['qty'],
                ]);
                $v->changeStock(-$l['qty'], 'order', 'Đơn hàng ' . $order->code, $user->id);
                if ($l['flashItemId']) {
                    FlashSaleItem::where('id', $l['flashItemId'])->increment('sold', $l['qty']);
                }
                Book::where('id', $v->book_id)->increment('sold_count', $l['qty']);
                UserActivity::log('purchase', $v->book_id);
            }
            if ($voucher) {
                $voucher->increment('used_count');
            }

            $order->histories()->create(['status' => 'pending', 'note' => 'Khách hàng đặt hàng', 'user_id' => $user->id]);
            return $order;
        });

        $this->cart->clear();
        FlashSale::resetCache();

        // Thông báo realtime
        UserNotification::send($user->id, 'Đặt hàng thành công ' . $order->code,
            'Cảm ơn bạn! Đơn hàng ' . money($order->total) . ' đang chờ xác nhận.', route('orders.show', $order->code));
        UserNotification::sendToBackend('Đơn hàng mới ' . $order->code,
            $order->receiver_name . ' vừa đặt đơn ' . money($order->total), route('admin.orders.show', $order));

        // Phát hiện bất thường
        $this->fraud->check($order->load(['user', 'items']));

        // Đẩy vào hàng đợi (message queue) các việc chạy nền: gửi email xác nhận, cập nhật thống kê
        ProcessNewOrder::dispatch($order->id)->onConnection('database');

        return $order;
    }
}
