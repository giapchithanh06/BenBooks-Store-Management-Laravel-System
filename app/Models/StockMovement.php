<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    const TYPES = [
        'import' => 'Nhập kho',
        'export' => 'Xuất kho',
        'order'  => 'Bán hàng',
        'cancel' => 'Hủy đơn – hoàn kho',
        'return' => 'Khách trả hàng',
        'adjust' => 'Điều chỉnh',
    ];

    protected $fillable = ['variant_id', 'user_id', 'type', 'quantity', 'stock_after', 'note'];

    public function variant() { return $this->belongsTo(BookVariant::class, 'variant_id'); }
    public function user()    { return $this->belongsTo(User::class); }
    public function getTypeNameAttribute() { return self::TYPES[$this->type] ?? $this->type; }
}
