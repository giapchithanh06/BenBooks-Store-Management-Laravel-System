<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'book_id', 'variant_id', 'flash_sale_item_id', 'book_title', 'variant_name', 'price', 'quantity', 'total'];

    public function order()   { return $this->belongsTo(Order::class); }
    public function book()    { return $this->belongsTo(Book::class)->withTrashed(); }
    public function variant() { return $this->belongsTo(BookVariant::class, 'variant_id'); }
}
