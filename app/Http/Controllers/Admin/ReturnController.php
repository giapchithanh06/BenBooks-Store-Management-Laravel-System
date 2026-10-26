<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns = ReturnRequest::with(['order', 'user', 'items.orderItem'])
            ->when($request->status, function ($q, $s) { $q->where('status', $s); })
            ->latest()->paginate(15)->withQueryString();
        return view('admin.returns.index', compact('returns'));
    }

    /**
     * pending -> approved | rejected ; approved -> completed (nhập lại kho, hoàn tiền nếu là trả hàng)
     */
    public function update(Request $request, ReturnRequest $returnRequest)
    {
        $data = $request->validate(['action' => 'required|in:approve,reject,complete', 'admin_note' => 'nullable|string|max:250']) + ['admin_note' => null];
        $rr = $returnRequest->load(['order.items', 'items.orderItem.variant']);
        $order = $rr->order;

        try {
        DB::transaction(function () use ($data, $rr, $order) {
            if ($data['action'] === 'approve' && $rr->status === 'pending') {
                $rr->update(['status' => 'approved', 'admin_note' => $data['admin_note']]);
            } elseif ($data['action'] === 'reject' && $rr->status === 'pending') {
                $rr->update(['status' => 'rejected', 'admin_note' => $data['admin_note']]);
            } elseif ($data['action'] === 'complete' && $rr->status === 'approved') {
                foreach ($rr->items as $ri) {
                    $variant = optional($ri->orderItem)->variant;
                    if ($variant) {
                        $variant->changeStock($ri->quantity, 'return', 'Khách ' . ($rr->type === 'return' ? 'trả' : 'đổi') . ' – đơn ' . $order->code, Auth::id());
                        if ($rr->type === 'exchange') {
                            // gửi sản phẩm mới thay thế
                            $variant->changeStock(-$ri->quantity, 'export', 'Xuất đổi hàng – đơn ' . $order->code, Auth::id());
                        }
                    }
                }
                $rr->update(['status' => 'completed', 'admin_note' => $data['admin_note'] ?: $rr->admin_note]);
                if ($rr->type === 'return') {
                    $returnedAll = $rr->items->sum('quantity') >= $order->items->sum('quantity');
                    if ($returnedAll) {
                        $order->update(['status' => 'returned', 'payment_status' => 'refunded']);
                    }
                    $order->histories()->create(['status' => $order->status, 'user_id' => Auth::id(),
                        'note' => 'Hoàn tất trả hàng, hoàn tiền ' . money($rr->refund_amount)]);
                } else {
                    $order->histories()->create(['status' => $order->status, 'user_id' => Auth::id(), 'note' => 'Hoàn tất đổi hàng']);
                }
            } else {
                throw new \RuntimeException('Thao tác không hợp lệ với trạng thái hiện tại.');
            }
        });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        UserNotification::send($rr->user_id, 'Yêu cầu đổi/trả ' . $order->code . ': ' . $rr->fresh()->status_name,
            $data['admin_note'] ?: 'Yêu cầu của bạn đã được cập nhật.', route('orders.show', $order->code));
        return back()->with('success', 'Đã cập nhật yêu cầu đổi/trả.');
    }
}
