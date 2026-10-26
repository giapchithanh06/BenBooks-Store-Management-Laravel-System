<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const ROLES = [
        'admin'    => 'Quản trị viên',
        'staff'    => 'Nhân viên',
        'shipper'  => 'Nhân viên giao hàng',
        'customer' => 'Khách hàng',
    ];

    protected $fillable = ['name', 'email', 'phone', 'password', 'role', 'is_active', 'segment'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['is_active' => 'boolean', 'email_verified_at' => 'datetime'];

    public function isAdmin()    { return $this->role === 'admin'; }
    public function isStaff()    { return $this->role === 'staff'; }
    public function isBackend()  { return in_array($this->role, ['admin', 'staff']); }
    public function isShipper()  { return $this->role === 'shipper'; }
    public function getRoleNameAttribute() { return self::ROLES[$this->role] ?? $this->role; }

    public function addresses()     { return $this->hasMany(Address::class)->orderByDesc('is_default'); }
    public function orders()        { return $this->hasMany(Order::class)->latest(); }
    public function wishlistBooks() { return $this->belongsToMany(Book::class, 'wishlists')->withTimestamps(); }
    public function reviews()       { return $this->hasMany(Review::class); }
    public function notifications_() { return $this->hasMany(UserNotification::class)->latest(); }
    public function deliveries()    { return $this->hasMany(Order::class, 'shipper_id')->latest(); }
    public function activities()    { return $this->hasMany(UserActivity::class)->latest(); }
    public function viewedBooks()   { return $this->belongsToMany(Book::class, 'book_views')->withTimestamps(); }

    public function unreadNotificationCount()
    {
        return UserNotification::where('user_id', $this->id)->where('is_read', false)->count();
    }

    public function defaultAddress()
    {
        return $this->addresses()->first();
    }

    public function hasPurchased($bookId)
    {
        return OrderItem::where('book_id', $bookId)
            ->whereHas('order', function ($q) {
                $q->where('user_id', $this->id)->where('status', 'delivered');
            })->exists();
    }
}
