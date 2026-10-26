<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['user_id', 'session_id', 'question', 'answer', 'intent'];

    public function user() { return $this->belongsTo(User::class); }
}
