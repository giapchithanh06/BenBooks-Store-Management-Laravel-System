<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    const STATUSES = [
        'pending'   => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping'  => 'Đang giao',
        'delivered' => 'Đã giao',
        'cancelled' => 'Đã hủy',
        'returned'  => 'Đã trả hàng',
    ];

    const STATUS_COLORS = [
        'pending' => 'warning', 'confirmed' => 'info', 'shipping' => 'primary',
        'delivered' => 'success', 'cancelled' => 'secondary', 'returned' => 'dark',
    ];

    /** Trạng thái được phép chuyển tiếp */
    const FLOW = [
        'pending'   => ['confirmed', 'cancelled'],
        'confirmed' => ['shipping', 'cancelled'],
        'shipping'  => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
        'returned'  => [],
    ];

    const PAYMENT_METHODS = ['cod' => 'Thanh toán khi nhận hàng (COD)', 'online' => 'Thanh toán online (ATM/Ví/Thẻ)'];
    const PAYMENT_STATUSES = ['unpaid' => 'Chưa thanh toán', 'paid' => 'Đã thanh toán', 'failed' => 'Thanh toán lỗi', 'refunded' => 'Đã hoàn tiền'];

    protected $fillable = [
        'code', 'user_id', 'receiver_name', 'phone', 'address', 'note', 'subtotal', 'discount', 'shipping_fee',
        'total', 'voucher_id', 'voucher_code', 'payment_method', 'payment_status', 'status',
        'shipping_carrier', 'tracking_code', 'cancel_reason', 'paid_at', 'delivered_at',
        'shipper_id', 'fraud_score', 'fraud_flags',
    ];

    protected $casts = ['paid_at' => 'datetime', 'delivered_at' => 'datetime'];

    public function user()      { return $this->belongsTo(User::class); }
    public function items()     { return $this->hasMany(OrderItem::class); }
    public function histories() { return $this->hasMany(OrderStatusHistory::class)->orderBy('id'); }
    public function payments()  { return $this->hasMany(Payment::class)->latest(); }
    public function voucher()   { return $this->belongsTo(Voucher::class); }
    public function shipper()   { return $this->belongsTo(User::class, 'shipper_id'); }
    public function returnRequests() { return $this->hasMany(ReturnRequest::class)->latest(); }

    public function getStatusNameAttribute()  { return self::STATUSES[$this->status] ?? $this->status; }
    public function getStatusColorAttribute() { return self::STATUS_COLORS[$this->status] ?? 'secondary'; }
    public function getPaymentMethodNameAttribute() { return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method; }
    public function getPaymentStatusNameAttribute() { return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status; }

    public function canCancel() { return in_array($this->status, ['pending', 'confirmed']); }

    /** Cho phép yêu cầu đổi/trả trong 7 ngày sau khi giao */
    public function canRequestReturn()
    {
        return $this->status === 'delivered'
            && $this->delivered_at && $this->delivered_at->gt(now()->subDays(7))
            && !$this->returnRequests()->whereIn('status', ['pending', 'approved'])->exists();
    }

    public function needsOnlinePayment()
    {
        return $this->payment_method === 'online' && $this->payment_status !== 'paid' && $this->status === 'pending';
    }

    public static function generateCode()
    {
        do {
            $code = 'MS' . now()->format('ymd') . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5));
        } while (static::where('code', $code)->exists());
        return $code;
    }

    /**
     * Đổi trạng thái đơn + ghi lịch sử + thông báo cho khách + xử lý kho.
     */
    public function changeStatus($status, $note = null, $byUserId = null)
    {
        DB::transaction(function () use ($status, $note, $byUserId) {
            if ($status === 'cancelled') {
                foreach ($this->items as $item) {
                    if ($item->variant) {
                        $item->variant->changeStock($item->quantity, 'cancel', 'Hủy đơn ' . $this->code, $byUserId);
                    }
                    if ($item->flash_sale_item_id) {
                        FlashSaleItem::where('id', $item->flash_sale_item_id)->decrement('sold', $item->quantity);
                    }
                    if ($item->book_id) {
                        Book::withTrashed()->where('id', $item->book_id)->decrement('sold_count', $item->quantity);
                    }
                }
                if ($this->voucher_id) {
                    Voucher::where('id', $this->voucher_id)->where('used_count', '>', 0)->decrement('used_count');
                }
                $this->cancel_reason = $note;
                if ($this->payment_status === 'paid') {
                    $this->payment_status = 'refunded';
                }
            }

            if ($status === 'delivered') {
                $this->delivered_at = now();
                if ($this->payment_method === 'cod') {
                    $this->payment_status = 'paid';
                    $this->paid_at = now();
                }
            }

            $this->status = $status;
            $this->save();

            $this->histories()->create(['status' => $status, 'note' => $note, 'user_id' => $byUserId]);

            UserNotification::send(
                $this->user_id,
                'Đơn hàng ' . $this->code . ': ' . $this->status_name,
                $note ?: 'Trạng thái đơn hàng của bạn vừa được cập nhật.',
                route('orders.show', $this->code)
            );
        });
    }
}
