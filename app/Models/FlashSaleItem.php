<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSaleItem extends Model
{
    protected $fillable = ['flash_sale_id', 'variant_id', 'sale_price', 'quantity', 'sold'];

    public function flashSale() { return $this->belongsTo(FlashSale::class); }
    public function variant()   { return $this->belongsTo(BookVariant::class, 'variant_id'); }

    public function getRemainingAttribute() { return max(0, $this->quantity - $this->sold); }
    public function getSoldPercentAttribute()
    {
        return $this->quantity ? (int) round($this->sold * 100 / $this->quantity) : 0;
    }
}
