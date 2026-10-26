<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = ['user_id', 'receiver_name', 'phone', 'province', 'district', 'ward', 'detail', 'is_default'];
    protected $casts = ['is_default' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }

    public function getFullAttribute()
    {
        return "{$this->detail}, {$this->ward}, {$this->district}, {$this->province}";
    }
}
