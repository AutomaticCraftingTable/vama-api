<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'author',
        'title',
        'content',
        'tags',
        'thumbnail',
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

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'target');
    }
}
