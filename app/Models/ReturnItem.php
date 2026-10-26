<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['return_request_id', 'order_item_id', 'quantity'];

    public function orderItem() { return $this->belongsTo(OrderItem::class); }
}
