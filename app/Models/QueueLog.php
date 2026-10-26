<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueLog extends Model
{
    protected $fillable = ['job', 'message'];

    public static function write($job, $message)
    {
        return static::create(['job' => $job, 'message' => mb_substr($message, 0, 250)]);
    }
}
