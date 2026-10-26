<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookVector extends Model
{
    protected $fillable = ['book_id', 'vector', 'norm'];
    protected $casts = ['vector' => 'array', 'norm' => 'float'];

    public function book() { return $this->belongsTo(Book::class); }
}
