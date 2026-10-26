<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserActivity extends Model
{
    const ACTIONS = [
        'view'     => 'Xem sách',
        'search'   => 'Tìm kiếm',
        'add_cart' => 'Thêm giỏ',
        'wishlist' => 'Yêu thích',
        'purchase' => 'Mua hàng',
    ];

    protected $fillable = ['user_id', 'session_id', 'action', 'book_id', 'keyword'];

    public function user() { return $this->belongsTo(User::class); }
    public function book() { return $this->belongsTo(Book::class)->withTrashed(); }

    /** Ghi nhận hành vi của người dùng hiện tại (khách vãng lai dùng session id) */
    public static function log($action, $bookId = null, $keyword = null)
    {
        try {
            return static::create([
                'user_id'    => Auth::id(),
                'session_id' => session()->getId(),
                'action'     => $action,
                'book_id'    => $bookId,
                'keyword'    => $keyword ? mb_substr($keyword, 0, 180) : null,
            ]);
        } catch (\Throwable $e) {
            return null; // không để việc ghi log làm hỏng trang
        }
    }
}
