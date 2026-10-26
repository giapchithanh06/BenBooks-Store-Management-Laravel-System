<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeDoc extends Model
{
    const CATEGORIES = ['policy' => 'Chính sách', 'guide' => 'Hướng dẫn', 'faq' => 'Câu hỏi thường gặp', 'about' => 'Giới thiệu'];

    protected $fillable = ['title', 'category', 'content', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function getCategoryNameAttribute() { return self::CATEGORIES[$this->category] ?? $this->category; }
}
