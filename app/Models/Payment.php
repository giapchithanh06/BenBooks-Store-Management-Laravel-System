<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['order_id', 'method', 'amount', 'transaction_code', 'status', 'bank_code'];

    public function order() { return $this->belongsTo(Order::class); }
}
