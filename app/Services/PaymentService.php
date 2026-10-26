<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\UserNotification;

/**
 * Thanh toán online – cổng thanh toán MÔ PHỎNG (sandbox) theo luồng giống VNPay:
 *   1. Website tạo URL thanh toán có chữ ký HMAC-SHA512 (chống sửa số tiền).
 *   2. Người dùng thanh toán trên trang cổng (giả lập), cổng redirect về Return URL kèm chữ ký.
 *   3. Website kiểm tra chữ ký, cập nhật trạng thái đơn.
 * Khi tích hợp thật chỉ cần thay URL cổng + secret của nhà cung cấp.
 */
class PaymentService
{
    protected function secret()
    {
        return env('PAYMENT_SECRET', 'MOTSACH_SANDBOX_SECRET_2026');
    }

    public function sign(array $params)
    {
        unset($params['signature']);
        ksort($params);
        return hash_hmac('sha512', http_build_query($params), $this->secret());
    }

    public function verify(array $params)
    {
        return isset($params['signature']) && hash_equals($this->sign($params), (string) $params['signature']);
    }

    /** URL chuyển sang cổng thanh toán */
    public function createPaymentUrl(Order $order)
    {
        $txn = $order->code . '-' . time();
        Payment::create(['order_id' => $order->id, 'method' => 'online', 'amount' => $order->total,
                         'transaction_code' => $txn, 'status' => 'pending']);
        $params = [
            'order_code' => $order->code,
            'amount'     => $order->total,
            'txn_ref'    => $txn,
            'info'       => 'Thanh toan don hang ' . $order->code,
            'return_url' => route('payment.return'),
        ];
        $params['signature'] = $this->sign($params);
        return route('payment.gateway', $params);
    }

    /** Xử lý kết quả cổng trả về. Trả về [bool success, string message, Order|null] */
    public function handleReturn(array $params)
    {
        if (!$this->verify($params)) {
            return [false, 'Chữ ký không hợp lệ – giao dịch bị từ chối.', null];
        }
        $order = Order::where('code', $params['order_code'] ?? '')->first();
        if (!$order) return [false, 'Không tìm thấy đơn hàng.', null];
        if ((int) $params['amount'] !== (int) $order->total) return [false, 'Số tiền không khớp.', $order];

        $payment = Payment::where('transaction_code', $params['txn_ref'] ?? '')->first();
        $success = ($params['response_code'] ?? '') === '00';
        if ($payment) {
            $payment->update(['status' => $success ? 'success' : 'failed', 'bank_code' => $params['bank_code'] ?? null]);
        }

        if ($success && $order->payment_status !== 'paid') {
            $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
            $order->histories()->create(['status' => $order->status, 'note' => 'Thanh toán online thành công (' . ($params['bank_code'] ?? '') . ')']);
            UserNotification::send($order->user_id, 'Thanh toán thành công ' . $order->code,
                'Đã nhận ' . money($order->total) . ' cho đơn hàng của bạn.', route('orders.show', $order->code));
            return [true, 'Thanh toán thành công!', $order];
        }
        if (!$success) {
            $order->update(['payment_status' => 'failed']);
            return [false, 'Thanh toán không thành công hoặc đã bị hủy. Bạn có thể thanh toán lại trong trang đơn hàng.', $order];
        }
        return [true, 'Đơn hàng đã được thanh toán trước đó.', $order];
    }
}
