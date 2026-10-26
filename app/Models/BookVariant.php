<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookVariant extends Model
{
    protected $fillable = ['book_id', 'name', 'sku', 'price', 'original_price', 'stock'];

    public function book() { return $this->belongsTo(Book::class)->withTrashed(); }
    public function movements() { return $this->hasMany(StockMovement::class, 'variant_id')->latest(); }

    /** Suất flash sale đang áp dụng (nếu có) */
    public function getFlashItemAttribute()
    {
        $items = FlashSale::currentItems();
        return $items[$this->id] ?? null;
    }

    /** Giá bán thực tế: ưu tiên giá flash sale */
    public function getEffectivePriceAttribute()
    {
        $item = $this->flash_item;
        return $item ? (int) $item->sale_price : (int) $this->price;
    }

    /** Giá gạch ngang */
    public function getCompareAtPriceAttribute()
    {
        if ($this->flash_item) {
            return (int) $this->price;
        }
        return ($this->original_price && $this->original_price > $this->price) ? (int) $this->original_price : null;
    }

    public function getDiscountPercentAttribute()
    {
        $c = $this->compare_at_price;
        return $c ? (int) round(100 - $this->effective_price * 100 / $c) : 0;
    }

    /**
     * Thay đổi tồn kho + ghi lịch sử. $qty dương = nhập, âm = xuất.
     */
    public function changeStock($qty, $type, $note = null, $userId = null)
    {
        $this->stock = $this->stock + $qty;
        $this->save();

        StockMovement::create([
            'variant_id'  => $this->id,
            'user_id'     => $userId,
            'type'        => $type,
            'quantity'    => $qty,
            'stock_after' => $this->stock,
            'note'        => $note,
        ]);
    }
}
