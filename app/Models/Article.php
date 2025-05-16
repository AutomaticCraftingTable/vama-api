<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'author',
        'title',
        'content',
        'tags',
        'banned_at',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'author', 'nickname');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(LikeReaction::class);
    }
}
