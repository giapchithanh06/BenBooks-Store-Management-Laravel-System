<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'value', 'max_discount', 'min_order', 'usage_limit',
        'used_count', 'per_user_limit', 'starts_at', 'ends_at', 'is_active',
    ];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];

    public function orders() { return $this->hasMany(Order::class); }

    public function getDescriptionAttribute()
    {
        $txt = $this->type === 'percent'
            ? "Giảm {$this->value}%" . ($this->max_discount ? ' (tối đa ' . money($this->max_discount) . ')' : '')
            : 'Giảm ' . money($this->value);
        if ($this->min_order > 0) {
            $txt .= ' cho đơn từ ' . money($this->min_order);
        }
        return $txt;
    }

    /** Trả về chuỗi lỗi nếu không dùng được, null nếu hợp lệ */
    public function checkUsable($subtotal, $user = null)
    {
        $now = now();
        if (!$this->is_active) return 'Mã giảm giá đã bị khóa.';
        if ($this->starts_at && $now->lt($this->starts_at)) return 'Mã giảm giá chưa đến thời gian áp dụng.';
        if ($this->ends_at && $now->gt($this->ends_at)) return 'Mã giảm giá đã hết hạn.';
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) return 'Mã giảm giá đã hết lượt sử dụng.';
        if ($subtotal < $this->min_order) return 'Đơn hàng tối thiểu ' . money($this->min_order) . ' để dùng mã này.';
        if ($user) {
            $used = Order::where('user_id', $user->id)->where('voucher_id', $this->id)
                ->where('status', '!=', 'cancelled')->count();
            if ($used >= $this->per_user_limit) return 'Bạn đã dùng hết lượt cho mã này.';
        }
        return null;
    }

    public function discountFor($subtotal)
    {
        if ($this->type === 'percent') {
            $d = (int) floor($subtotal * $this->value / 100);
            if ($this->max_discount) $d = min($d, $this->max_discount);
        } else {
            $d = (int) $this->value;
        }
        return min($d, $subtotal);
    }
}
