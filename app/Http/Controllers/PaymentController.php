<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;

/**
 * Cổng thanh toán MÔ PHỎNG (sandbox) để demo luồng thanh toán online có ký số HMAC.
 */
class PaymentController extends Controller
{
    const BANKS = ['NCB' => 'Ngân hàng NCB', 'VCB' => 'Vietcombank', 'TCB' => 'Techcombank', 'MOMO' => 'Ví MoMo', 'VISA' => 'Thẻ Visa/Master'];

    public function gateway(Request $request, PaymentService $payment)
    {
        $params = $request->only(['order_code', 'amount', 'txn_ref', 'info', 'return_url', 'signature']);
        if (!$payment->verify($params)) {
            abort(400, 'Yêu cầu thanh toán không hợp lệ (sai chữ ký).');
        }
        $banks = self::BANKS;
        return view('shop.payment-gateway', compact('params', 'banks'));
    }

    /** Người dùng bấm Thanh toán / Hủy trên cổng -> cổng ký kết quả và chuyển về website */
    public function gatewaySubmit(Request $request, PaymentService $payment)
    {
        $params = $request->only(['order_code', 'amount', 'txn_ref', 'info', 'return_url', 'signature']);
        if (!$payment->verify($params)) {
            abort(400, 'Yêu cầu thanh toán không hợp lệ.');
        }
        $result = [
            'order_code'    => $params['order_code'],
            'amount'        => $params['amount'],
            'txn_ref'       => $params['txn_ref'],
            'bank_code'     => $request->input('bank_code', 'NCB'),
            'response_code' => $request->input('action') === 'pay' ? '00' : '24', // 24 = khách hủy giao dịch
            'pay_date'      => now()->format('YmdHis'),
        ];
        $result['signature'] = $payment->sign($result);
        return redirect()->away($params['return_url'] . '?' . http_build_query($result));
    }

    public function paymentReturn(Request $request, PaymentService $payment)
    {
        list($ok, $message, $order) = $payment->handleReturn($request->query());
        if (!$order) {
            return redirect()->route('home')->with('error', $message);
        }
        if ($ok) {
            return redirect()->route('checkout.success', $order->code)->with('success', $message);
        }
        return redirect()->route('orders.show', $order->code)->with('error', $message);
    }
}
