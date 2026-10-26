<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    const SENTIMENTS = ['positive' => 'Tích cực', 'neutral' => 'Trung lập', 'negative' => 'Tiêu cực'];
    const SENTIMENT_COLORS = ['positive' => 'success', 'neutral' => 'secondary', 'negative' => 'danger'];

    protected $fillable = ['book_id', 'user_id', 'rating', 'comment', 'is_visible', 'sentiment', 'sentiment_score', 'aspects'];
    protected $casts = ['is_visible' => 'boolean', 'sentiment_score' => 'float', 'aspects' => 'array'];

    public function book() { return $this->belongsTo(Book::class)->withTrashed(); }
    public function user() { return $this->belongsTo(User::class); }

    public function getSentimentNameAttribute()  { return self::SENTIMENTS[$this->sentiment] ?? 'Chưa phân tích'; }
    public function getSentimentColorAttribute() { return self::SENTIMENT_COLORS[$this->sentiment] ?? 'light'; }
}
