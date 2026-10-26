<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Ứng dụng giao hàng cho nhân viên giao hàng (giao diện tối ưu cho điện thoại).
 * Admin phân công đơn -> shipper nhận hàng -> cập nhật giao thành công / thất bại -> khách nhận thông báo.
 */
class ShipperController extends Controller
{
    protected function myOrder(Order $order)
    {
        $user = Auth::user();
        abort_if(!$user->isAdmin() && $order->shipper_id != $user->id, 403, 'Đơn này không được phân công cho bạn.');
        return $order;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->input('tab', 'todo');
        $q = Order::with('items')->when(!$user->isAdmin(), function ($q) use ($user) { $q->where('shipper_id', $user->id); });
        if ($tab === 'done') {
            $q->whereIn('status', ['delivered', 'cancelled', 'returned']);
        } else {
            $q->whereIn('status', ['confirmed', 'shipping']);
        }
        $orders = $q->latest()->take(50)->get();
        $todayDelivered = Order::where('shipper_id', $user->id)->where('status', 'delivered')->whereDate('delivered_at', today())->count();
        $codToCollect = Order::where('shipper_id', $user->id)->where('status', 'shipping')->where('payment_status', '!=', 'paid')->sum('total');
        return view('shipper.index', compact('orders', 'tab', 'todayDelivered', 'codToCollect'));
    }

    public function show(Order $order)
    {
        $this->myOrder($order)->load(['items', 'histories']);
        return view('shipper.show', compact('order'));
    }

    public function pickup(Order $order)
    {
        $this->myOrder($order);
        if ($order->status !== 'confirmed') return back()->with('error', 'Đơn không ở trạng thái chờ lấy hàng.');
        if (!$order->tracking_code) {
            $order->update(['shipping_carrier' => $order->shipping_carrier ?: 'BenBooks Express', 'tracking_code' => 'MSX' . strtoupper(substr(md5($order->code), 0, 8))]);
        }
        $order->changeStatus('shipping', 'Shipper ' . Auth::user()->name . ' đã lấy hàng, đang giao đến bạn.', Auth::id());
        return back()->with('success', 'Đã nhận đơn, bắt đầu giao.');
    }

    public function delivered(Order $order)
    {
        $this->myOrder($order);
        if ($order->status !== 'shipping') return back()->with('error', 'Đơn chưa ở trạng thái đang giao.');
        $order->changeStatus('delivered', 'Giao hàng thành công' . ($order->payment_method === 'cod' ? ' – đã thu ' . money($order->total) : '') . '.', Auth::id());
        return redirect()->route('shipper.index')->with('success', 'Đã xác nhận giao thành công.');
    }

    public function failed(Request $request, Order $order)
    {
        $this->myOrder($order);
        $request->validate(['reason' => 'required|string|max:200'], [], ['reason' => 'lý do']);
        $order->histories()->create(['status' => $order->status, 'note' => 'Giao không thành công: ' . $request->reason, 'user_id' => Auth::id()]);
        UserNotification::send($order->user_id, 'Giao hàng chưa thành công ' . $order->code,
            'Lý do: ' . $request->reason . '. Shipper sẽ liên hệ giao lại.', route('orders.show', $order->code));
        UserNotification::sendToBackend('Giao thất bại ' . $order->code, $request->reason, route('admin.orders.show', $order));
        return back()->with('success', 'Đã ghi nhận giao không thành công.');
    }
}
