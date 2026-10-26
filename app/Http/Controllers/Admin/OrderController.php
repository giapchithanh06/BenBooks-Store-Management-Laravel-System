<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    const CARRIERS = ['BenBooks Express', 'Giao Hàng Nhanh (GHN)', 'Giao Hàng Tiết Kiệm (GHTK)', 'Viettel Post', 'J&T Express'];

    public function index(Request $request)
    {
        $q = Order::with('user')->withCount('items');
        if ($kw = $request->input('q')) {
            $q->where(function ($w) use ($kw) {
                $w->where('code', 'like', "%$kw%")->orWhere('receiver_name', 'like', "%$kw%")->orWhere('phone', 'like', "%$kw%");
            });
        }
        if ($s = $request->input('status')) $q->where('status', $s);
        if ($p = $request->input('payment_status')) $q->where('payment_status', $p);
        if ($request->input('from')) $q->whereDate('created_at', '>=', $request->input('from'));
        if ($request->input('to')) $q->whereDate('created_at', '<=', $request->input('to'));
        if ($request->boolean('risky')) $q->where('fraud_score', '>=', 50);

        $counts = Order::select('status', DB::raw('COUNT(*) as c'))->groupBy('status')->pluck('c', 'status');
        return view('admin.orders.index', ['orders' => $q->latest()->paginate(20)->withQueryString(), 'counts' => $counts]);
    }

    public function show(Order $order)
    {
        $order->load(['items.book', 'histories.user', 'payments', 'user', 'returnRequests', 'shipper', 'voucher']);
        return view('admin.orders.show', [
            'order'    => $order,
            'shippers' => User::where('role', 'shipper')->where('is_active', true)->get(),
            'carriers' => self::CARRIERS,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $allowed = Order::FLOW[$order->status] ?? [];
        $request->validate(['status' => 'required|in:' . implode(',', $allowed ?: ['__none__']), 'note' => 'nullable|string|max:200'],
            ['status.in' => 'Không thể chuyển từ "' . $order->status_name . '" sang trạng thái này.']);
        $order->changeStatus($request->status, $request->note, Auth::id());
        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng: ' . $order->status_name);
    }

    /** Phân công shipper + đơn vị vận chuyển + mã vận đơn (tích hợp app giao hàng) */
    public function updateShipping(Request $request, Order $order)
    {
        $data = $request->validate([
            'shipper_id'       => 'nullable|exists:users,id',
            'shipping_carrier' => 'nullable|string|max:50',
            'tracking_code'    => 'nullable|string|max:50',
        ]);
        if (!empty($data['shipper_id']) && empty($data['tracking_code'])) {
            $data['tracking_code'] = 'MSX' . strtoupper(substr(md5($order->code), 0, 8));
            $data['shipping_carrier'] = $data['shipping_carrier'] ?? 'BenBooks Express';
        }
        $order->update($data);
        if (!empty($data['shipper_id'])) {
            UserNotification::send($data['shipper_id'], 'Đơn giao mới ' . $order->code,
                'Giao tới ' . $order->receiver_name . ' – ' . $order->address, route('shipper.show', $order));
        }
        return back()->with('success', 'Đã cập nhật thông tin vận chuyển.');
    }

    public function invoice(Order $order)
    {
        $order->load('items');
        return view('admin.orders.invoice', compact('order'));
    }
}
