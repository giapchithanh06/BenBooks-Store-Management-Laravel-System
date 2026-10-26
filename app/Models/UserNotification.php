<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $fillable = ['user_id', 'title', 'content', 'link', 'is_read'];
    protected $casts = ['is_read' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }

    public static function send($userId, $title, $content, $link = null)
    {
        return static::create(compact('title', 'content', 'link') + ['user_id' => $userId]);
    }

    /** Gửi cho toàn bộ admin + nhân viên */
    public static function sendToBackend($title, $content, $link = null)
    {
        foreach (User::whereIn('role', ['admin', 'staff'])->pluck('id') as $id) {
            static::send($id, $title, $content, $link);
        }
    }
}
