<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'parent_id', 'description', 'sort_order'];

    public function parent()   { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children() { return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order'); }
    public function books()    { return $this->hasMany(Book::class); }

    /** id của danh mục + toàn bộ danh mục con (dùng khi lọc) */
    public function descendantIds()
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }
        return $ids;
    }

    public static function menuTree()
    {
        return static::whereNull('parent_id')->with('children')->orderBy('sort_order')->get();
    }
}
