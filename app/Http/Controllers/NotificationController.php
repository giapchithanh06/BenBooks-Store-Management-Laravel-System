<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Thông báo (đơn hàng, khuyến mãi, cảnh báo...). Realtime bằng AJAX polling mỗi 10 giây
 * – chạy được trên XAMPP không cần WebSocket server.
 */
class NotificationController extends Controller
{
    public function index()
    {
        $notifications = UserNotification::where('user_id', Auth::id())->latest()->paginate(20);
        $layout = Auth::user()->isBackend() ? 'layouts.admin' : 'layouts.shop';
        return view('shop.account.notifications', compact('notifications', 'layout'));
    }

    public function open(UserNotification $notification)
    {
        abort_if($notification->user_id != Auth::id(), 403);
        $notification->update(['is_read' => true]);
        return redirect($notification->link ?: route('notifications.index'));
    }

    public function readAll()
    {
        UserNotification::where('user_id', Auth::id())->update(['is_read' => true]);
        return back()->with('success', 'Đã đánh dấu tất cả là đã đọc.');
    }

    /** API polling: số chưa đọc + các thông báo mới hơn after_id */
    public function poll(Request $request)
    {
        $after = (int) $request->input('after_id', 0);
        $q = UserNotification::where('user_id', Auth::id());
        $new = (clone $q)->where('id', '>', $after)->latest('id')->take(5)->get();
        return response()->json([
            'unread'  => (clone $q)->where('is_read', false)->count(),
            'last_id' => (int) ((clone $q)->max('id') ?: 0),
            'items'   => $new->map(function ($n) {
                return ['id' => $n->id, 'title' => $n->title, 'content' => $n->content,
                        'url' => route('notifications.open', $n), 'time' => $n->created_at->diffForHumans()];
            }),
        ]);
    }
}
