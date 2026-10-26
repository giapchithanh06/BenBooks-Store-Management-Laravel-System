<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'category_id', 'publisher_id', 'isbn', 'publish_year', 'pages', 'language',
        'description', 'tags', 'cover_image', 'is_active', 'is_featured', 'view_count', 'sold_count',
        'rating_avg', 'rating_count',
    ];

    protected $casts = ['is_active' => 'boolean', 'is_featured' => 'boolean', 'rating_avg' => 'float'];

    public function category()  { return $this->belongsTo(Category::class); }
    public function publisher() { return $this->belongsTo(Publisher::class); }
    public function authors()   { return $this->belongsToMany(Author::class); }
    public function variants()  { return $this->hasMany(BookVariant::class)->orderBy('price'); }
    public function reviews()   { return $this->hasMany(Review::class)->latest(); }
    public function vector()    { return $this->hasOne(BookVector::class); }
    public function orderItems() { return $this->hasMany(OrderItem::class); }

    public function scopeActive($q) { return $q->where('is_active', true); }

    public function getCoverUrlAttribute()
    {
        return asset($this->cover_image ?: 'images/no-cover.png');
    }

    public function getAuthorNamesAttribute()
    {
        return $this->authors->pluck('name')->implode(', ');
    }

    public function getTotalStockAttribute()
    {
        return (int) $this->variants->sum('stock');
    }

    /** Biến thể rẻ nhất (sau khi tính flash sale) – dùng để hiển thị giá ở thẻ sách */
    public function getDisplayVariantAttribute()
    {
        return $this->variants->sortBy(function ($v) { return $v->effective_price; })->first();
    }

    public function recalcRating()
    {
        $q = Review::where('book_id', $this->id)->where('is_visible', true);
        $this->rating_count = $q->count();
        $this->rating_avg = $this->rating_count ? round($q->avg('rating'), 2) : 0;
        $this->save();
    }
}
