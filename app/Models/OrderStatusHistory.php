<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $fillable = ['order_id', 'user_id', 'status', 'note'];

    public function user() { return $this->belongsTo(User::class); }
    public function getStatusNameAttribute() { return Order::STATUSES[$this->status] ?? $this->status; }
}
