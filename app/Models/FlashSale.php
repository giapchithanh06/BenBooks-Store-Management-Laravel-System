<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    protected $fillable = ['name', 'starts_at', 'ends_at', 'is_active'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];

    /** cache trong 1 request */
    protected static $cachedItems = null;

    public function items() { return $this->hasMany(FlashSaleItem::class); }

    public function scopeRunning($q)
    {
        $now = now();
        return $q->where('is_active', true)->where('starts_at', '<=', $now)->where('ends_at', '>=', $now);
    }

    public function getStatusNameAttribute()
    {
        $now = now();
        if (!$this->is_active) return 'Tạm tắt';
        if ($now->lt($this->starts_at)) return 'Sắp diễn ra';
        if ($now->gt($this->ends_at)) return 'Đã kết thúc';
        return 'Đang diễn ra';
    }

    /** [variant_id => FlashSaleItem] của các flash sale đang chạy, còn suất */
    public static function currentItems()
    {
        if (static::$cachedItems === null) {
            static::$cachedItems = FlashSaleItem::whereHas('flashSale', function ($q) { $q->running(); })
                ->whereColumn('sold', '<', 'quantity')
                ->get()
                ->keyBy('variant_id')
                ->all();
        }
        return static::$cachedItems;
    }

    public static function current()
    {
        return static::running()->orderBy('ends_at')->first();
    }

    public static function resetCache()
    {
        static::$cachedItems = null;
    }
}
