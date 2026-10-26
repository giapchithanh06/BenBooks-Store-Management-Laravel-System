<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    const TYPES = ['return' => 'Trả hàng – hoàn tiền', 'exchange' => 'Đổi sản phẩm'];
    const STATUSES = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'completed' => 'Hoàn tất'];
    const STATUS_COLORS = ['pending' => 'warning', 'approved' => 'info', 'rejected' => 'danger', 'completed' => 'success'];

    protected $fillable = ['order_id', 'user_id', 'type', 'reason', 'status', 'refund_amount', 'admin_note'];

    public function order() { return $this->belongsTo(Order::class); }
    public function user()  { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(ReturnItem::class); }

    public function getTypeNameAttribute()    { return self::TYPES[$this->type] ?? $this->type; }
    public function getStatusNameAttribute()  { return self::STATUSES[$this->status] ?? $this->status; }
    public function getStatusColorAttribute() { return self::STATUS_COLORS[$this->status] ?? 'secondary'; }
}
