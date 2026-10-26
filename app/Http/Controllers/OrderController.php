<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\UserNotification;
use App\Services\CartService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected function findMine($code)
    {
        return Order::where('code', $code)->where('user_id', Auth::id())
            ->with(['items.book', 'histories', 'returnRequests.items', 'payments'])->firstOrFail();
    }

    public function index(Request $request)
    {
        $status = $request->input('status');
        $orders = Order::where('user_id', Auth::id())->with('items')
            ->when($status, function ($q) use ($status) { $q->where('status', $status); })
            ->latest()->paginate(10)->withQueryString();
        $counts = Order::where('user_id', Auth::id())->select('status', DB::raw('COUNT(*) as c'))->groupBy('status')->pluck('c', 'status');
        return view('shop.orders.index', compact('orders', 'status', 'counts'));
    }

    public function show($code)
    {
        $order = $this->findMine($code);
        return view('shop.orders.show', compact('order'));
    }

    public function cancel(Request $request, $code)
    {
        $order = $this->findMine($code);
        if (!$order->canCancel()) {
            return back()->with('error', 'Đơn hàng đã được xử lý, không thể hủy.');
        }
        $request->validate(['reason' => 'required|string|max:200'], [], ['reason' => 'lý do hủy']);
        $order->changeStatus('cancelled', 'Khách hủy: ' . $request->reason, Auth::id());
        UserNotification::sendToBackend('Khách hủy đơn ' . $order->code, $request->reason, route('admin.orders.show', $order));
        return back()->with('success', 'Đã hủy đơn hàng.');
    }

    public function repay($code, PaymentService $payment)
    {
        $order = $this->findMine($code);
        if (!$order->needsOnlinePayment()) {
            return back()->with('error', 'Đơn hàng này không cần thanh toán online.');
        }
        return redirect()->away($payment->createPaymentUrl($order));
    }

    /** Mua lại: thêm các sách của đơn cũ vào giỏ */
    public function reorder($code, CartService $cart)
    {
        $order = $this->findMine($code);
        $errors = [];
        foreach ($order->items as $it) {
            if ($it->variant_id && ($err = $cart->add($it->variant_id, $it->quantity))) $errors[] = $err;
        }
        return redirect()->route('cart.index')->with($errors ? 'error' : 'success', $errors ? implode(' ', $errors) : 'Đã thêm lại các sản phẩm vào giỏ.');
    }

    public function returnForm($code)
    {
        $order = $this->findMine($code);
        if (!$order->canRequestReturn()) {
            return redirect()->route('orders.show', $code)->with('error', 'Đơn hàng không đủ điều kiện đổi/trả (chỉ trong 7 ngày sau khi giao).');
        }
        return view('shop.orders.return', compact('order'));
    }

    public function returnStore(Request $request, $code)
    {
        $order = $this->findMine($code);
        if (!$order->canRequestReturn()) {
            return redirect()->route('orders.show', $code)->with('error', 'Đơn hàng không đủ điều kiện đổi/trả.');
        }
        $data = $request->validate([
            'type'   => 'required|in:return,exchange',
            'reason' => 'required|string|max:1000',
            'qty'    => 'required|array',
        ], [], ['type' => 'hình thức', 'reason' => 'lý do', 'qty' => 'số lượng']);

        $lines = [];
        $refund = 0;
        foreach ($order->items as $it) {
            $q = (int) ($data['qty'][$it->id] ?? 0);
            if ($q > 0) {
                $q = min($q, $it->quantity);
                $lines[] = ['order_item_id' => $it->id, 'quantity' => $q];
                $refund += $it->price * $q;
            }
        }
        if (!$lines) {
            return back()->withInput()->with('error', 'Vui lòng chọn ít nhất 1 sản phẩm cần đổi/trả.');
        }
        if ($order->discount > 0 && $order->subtotal > 0) {
            $refund = (int) round($refund * (1 - $order->discount / $order->subtotal)); // trừ phần giảm giá đã hưởng
        }

        $rr = ReturnRequest::create([
            'order_id' => $order->id, 'user_id' => Auth::id(), 'type' => $data['type'],
            'reason' => $data['reason'], 'status' => 'pending',
            'refund_amount' => $data['type'] === 'return' ? $refund : 0,
        ]);
        $rr->items()->createMany($lines);

        UserNotification::sendToBackend('Yêu cầu ' . mb_strtolower($rr->type_name) . ' – ' . $order->code,
            $data['reason'], route('admin.returns.index'));
        return redirect()->route('orders.show', $code)->with('success', 'Đã gửi yêu cầu, BenBooks sẽ phản hồi trong 24h.');
    }

    /** Tra cứu đơn không cần đăng nhập: mã đơn + số điện thoại */
    public function track(Request $request)
    {
        $order = null;
        if ($request->filled('code')) {
            $order = Order::where('code', strtoupper(trim($request->code)))
                ->where('phone', trim($request->phone))->with(['items', 'histories'])->first();
            if (!$order) {
                session()->now('error', 'Không tìm thấy đơn hàng với mã và số điện thoại này.');
            }
        }
        return view('shop.orders.track', compact('order'));
    }
}
